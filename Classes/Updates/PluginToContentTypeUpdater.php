<?php

declare(strict_types=1);

namespace Derhansen\FeChangePwd\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('feChangePwdPluginToContentTypeUpdater')]
class PluginToContentTypeUpdater implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'feChangePwdPluginToContentTypeUpdater';
    }

    public function getTitle(): string
    {
        return 'ext:fe_change_pwd: Migrates list_type plugin to CType content element';
    }

    public function getDescription(): string
    {
        return 'Migrates the plugin in ext:fe_change_pwd from list_type to CType';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->checkIfWizardIsRequired();
    }

    public function executeUpdate(): bool
    {
        return $this->performMigration();
    }

    public function checkIfWizardIsRequired(): bool
    {
        return count($this->getMigrationRecords()) > 0;
    }

    public function performMigration(): bool
    {
        $records = $this->getMigrationRecords();

        foreach ($records as $record) {
            $this->updateContentElement($record['uid'], $record['list_type']);
        }

        return true;
    }

    /**
     * Updates the CType and sets `list_type` to an empty string for the given tt_content record
     */
    protected function updateContentElement(int $uid, string $newCtype): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->update('tt_content')
            ->set('CType', $newCtype)
            ->set('list_type', '')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    /**
     * Returns all record for the migration
     */
    protected function getMigrationRecords(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'pid', 'CType', 'list_type', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('list')
                ),
                $queryBuilder->expr()->eq(
                    'list_type',
                    $queryBuilder->createNamedParameter('fechangepwd_pi1')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
