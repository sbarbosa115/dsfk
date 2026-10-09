<?php

declare(strict_types=1);

namespace App\Tests\Functional\Audit;

use App\Shared\Domain\Model\Audited;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The audit log stores each change under the field's name in the code ("voidedById"); the UI shows it by its Spanish
 * name from `audit.fields` in es.ts (QA-0008). A field added to an audited entity without its Spanish line would show
 * up raw in Auditoría: this test fails first.
 */
final class AuditFieldNamesTest extends KernelTestCase
{
    public function testEveryAuditedFieldHasItsSpanishName(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $translations = (string) file_get_contents(\dirname(__DIR__, 3).'/assets/react/shared/i18n/es.ts');
        $start = strpos($translations, "    fields: {\n");
        self::assertNotFalse($start, 'es.ts has an audit.fields block');
        $block = substr($translations, $start, (int) strpos($translations, "\n    },", $start) - $start);
        preg_match_all('/^\s{6}(\w+):/m', $block, $matches);
        $named = $matches[1];

        $missing = [];
        $audited = 0;
        foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
            if (!is_a($metadata->getName(), Audited::class, true)) {
                continue;
            }
            ++$audited;
            foreach ([...$metadata->getFieldNames(), ...$metadata->getAssociationNames()] as $field) {
                if (!\in_array($field, $named, true)) {
                    $missing[] = $metadata->getName().'::'.$field;
                }
            }
        }

        self::assertGreaterThan(10, $audited, 'the audited entities were found');
        self::assertSame([], $missing, 'add each one to audit.fields in assets/react/shared/i18n/es.ts');
    }
}
