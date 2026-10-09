/// <reference types="node" />
// Every group of buttons has at most one filled (main) action, no row keeps icons beside its control, and no action
// uses the generic dark-green or grey button (QA-0002, QA-0003; the MDX house style). Read from the source, so a page
// that breaks the rule fails here without being rendered.
import {readFileSync, readdirSync} from 'node:fs';
import {join} from 'node:path';
import ts from 'typescript';
import {describe, expect, it} from 'vitest';

const ROOT = join(process.cwd(), 'assets/react');

function sources(): string[] {
  return readdirSync(ROOT, {recursive: true, encoding: 'utf8'})
    .filter((file) => /\.tsx$/.test(file) && !/\.test\.tsx$/.test(file))
    .map((file) => file.split('\\').join('/'));
}

function parse(file: string): ts.SourceFile {
  return ts.createSourceFile(
    file,
    readFileSync(join(ROOT, file), 'utf8'),
    ts.ScriptTarget.Latest,
    true,
    ts.ScriptKind.TSX,
  );
}

type Jsx = ts.JsxElement | ts.JsxSelfClosingElement;

function tagOf(node: Jsx): string {
  return (
    ts.isJsxElement(node) ? node.openingElement.tagName : node.tagName
  ).getText();
}

function attribute(node: Jsx, name: string): ts.JsxAttribute | undefined {
  const attributes = ts.isJsxElement(node)
    ? node.openingElement.attributes
    : node.attributes;
  return attributes.properties.find(
    (prop): prop is ts.JsxAttribute =>
      ts.isJsxAttribute(prop) && prop.name.getText() === name,
  );
}

/** Filled: an ActionButton with `main` (not `main={false}`), a SubmitButton, or an element whose classes say `is-main`. */
function isFilled(node: Jsx): boolean {
  const tag = tagOf(node);
  if (tag === 'SubmitButton') {
    return true;
  }
  const main = attribute(node, 'main');
  if (tag === 'ActionButton' && main) {
    return main.initializer?.getText() !== '{false}';
  }
  const className = attribute(node, 'className');
  return (
    !!className?.initializer && /is-main/.test(className.initializer.getText())
  );
}

/** How many filled buttons a child of a group can render at once: a condition counts its bigger branch. */
function filledIn(node: ts.Node): number {
  if (ts.isJsxElement(node) || ts.isJsxSelfClosingElement(node)) {
    return isFilled(node) ? 1 : 0;
  }
  if (ts.isJsxFragment(node)) {
    return node.children.reduce((sum, child) => sum + filledIn(child), 0);
  }
  if (ts.isJsxExpression(node)) {
    return node.expression ? filledIn(node.expression) : 0;
  }
  if (ts.isParenthesizedExpression(node)) {
    return filledIn(node.expression);
  }
  if (ts.isBinaryExpression(node)) {
    return filledIn(node.right);
  }
  if (ts.isConditionalExpression(node)) {
    return Math.max(filledIn(node.whenTrue), filledIn(node.whenFalse));
  }
  // A list of buttons (`.map`) counts once: its `main` must pick one item, which the source cannot show.
  if (ts.isCallExpression(node)) {
    return Math.min(
      1,
      node.arguments.reduce((sum, arg) => sum + filledInFunction(arg), 0),
    );
  }
  return 0;
}

function filledInFunction(node: ts.Node): number {
  if (!ts.isArrowFunction(node) && !ts.isFunctionExpression(node)) {
    return 0;
  }
  if (!ts.isBlock(node.body)) {
    return filledIn(node.body);
  }
  let found = 0;
  node.body.forEachChild(function visit(child) {
    if (ts.isReturnStatement(child) && child.expression) {
      found = Math.max(found, filledIn(child.expression));
    } else if (!ts.isFunctionLike(child)) {
      child.forEachChild(visit);
    }
  });
  return found;
}

/** Every JSX element in a file. */
function elements(source: ts.SourceFile): Jsx[] {
  const found: Jsx[] = [];
  source.forEachChild(function visit(node) {
    if (ts.isJsxElement(node) || ts.isJsxSelfClosingElement(node)) {
      found.push(node);
    }
    node.forEachChild(visit);
  });
  return found;
}

function groupsWithTwoFilled(source: ts.SourceFile): ts.JsxElement[] {
  return elements(source)
    .filter((node): node is ts.JsxElement => ts.isJsxElement(node))
    .filter(
      (node) =>
        node.children.reduce((sum, child) => sum + filledIn(child), 0) > 1,
    );
}

const where = (source: ts.SourceFile, node: ts.Node) =>
  `${source.fileName}:${source.getLineAndCharacterOfPosition(node.getStart()).line + 1}`;

describe('Button groups', () => {
  const files = sources().map(parse);

  it("fill at most one button: the group's main action", () => {
    const offending = files.flatMap((source) =>
      groupsWithTwoFilled(source).map((node) => where(source, node)),
    );
    expect(
      offending,
      'one filled (`main`) action per group; the others outlined, "Cancelar" grey',
    ).toEqual([]);
  });

  it("fold the eye, the pencil and on/off into every row's control", () => {
    const offending = files.flatMap((source) =>
      elements(source)
        .filter(
          (node) => tagOf(node) === 'RowActions' && attribute(node, 'icons'),
        )
        .map((node) => where(source, node)),
    );
    expect(
      offending,
      'pass `view`, `edit` and `toggle` to RowActions instead of `icons`',
    ).toEqual([]);
  });

  it('never use the generic dark-green or grey button for an action', () => {
    const offending = sources().filter((file) =>
      /btn-(primary|secondary)\b|variant="(primary|secondary)"/.test(
        readFileSync(join(ROOT, file), 'utf8'),
      ),
    );
    expect(
      offending,
      'an action is an ActionButton in the colour of what it does',
    ).toEqual([]);
  });

  it('catch two filled buttons in one group, and one per branch of a condition', () => {
    const check = (code: string) =>
      groupsWithTwoFilled(
        ts.createSourceFile(
          'x.tsx',
          code,
          ts.ScriptTarget.Latest,
          true,
          ts.ScriptKind.TSX,
        ),
      ).length;
    expect(
      check('<div><ActionButton action="setup" main /><SubmitButton /></div>'),
    ).toBe(1);
    expect(
      check(
        '<div>{a && <ActionButton action="setup" main />}<ActionButton action="file" /></div>',
      ),
    ).toBe(0);
    expect(
      check(
        '<div>{a ? <ActionButton action="setup" main /> : <SubmitButton />}</div>',
      ),
    ).toBe(0);
    expect(
      check(
        "<div><Link className={actionClass('open', 'is-main')} /><>{b && <SubmitButton />}</></div>",
      ),
    ).toBe(1);
  });
});
