<?php

declare(strict_types=1);

namespace Derhansen\FeChangePwd\Updates;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('feChangePwdPluginToContentElementUpdate')]
class PluginToContentElementUpdater extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'fechangepwd_pi1' => 'fechangepwd_pi1',
        ];
    }

    public function getTitle(): string
    {
        return 'ext:fe_change_pwd: Migrate plugins to content elements';
    }

    public function getDescription(): string
    {
        return 'Migrates existing plugin records and backend user permissions used by ext:fe_change_pwd.';
    }
}
