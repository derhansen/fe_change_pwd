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

#[UpgradeWizard('feChangePwdPluginPermissionUpdater')]
class PluginPermissionUpdater implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'feChangePwdPluginPermissionUpdater';
    }

    public function getTitle(): string
    {
        return 'ext:fe_change_pwd: Migrates plugin permissions for the new CTypes content element';
    }

    public function getDescription(): string
    {
        return 'Migrates plugin permissions in ext:fe_change_pwd for the migrated CTypes content element';
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
            $this->updateExplicitAllowdeny(
                $record['uid'],
                str_replace('list_type:fechangepwd_pi1', 'CType:fechangepwd_pi1', $record['explicit_allowdeny'])
            );
        }

        return true;
    }

    /**
     * Updates the explicit_allowdeny for the given be_groups record uid with the given value
     */
    protected function updateExplicitAllowdeny(int $uid, string $explicitAllowdeny): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $queryBuilder->update('be_groups')
            ->set('explicit_allowdeny', $explicitAllowdeny)
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
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter('%list_type:fechangepwd_pi1%')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
