<?php

namespace BucoOrderDocumentsApi;

use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Plugin\Plugin;

class BucoOrderDocumentsApi extends \Shopware\Components\Plugin
{
    const CACHE_LIST = [InstallContext::CACHE_TAG_ROUTER];

    public function install(InstallContext $context)
    {
        $this->addApiAcls();
    }

    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(self::CACHE_LIST);
    }

    public function deactivate(DeactivateContext $context)
    {
        $context->scheduleClearCache(self::CACHE_LIST);
    }

    public function uninstall(UninstallContext $context)
    {
        if(!$context->keepUserData()) {
            $this->removeApiAcls();
        }
    }

    private function addApiAcls()
    {
        $aclService = $this->container->get('acl');
        $em = $this->container->get('models');

        /** @var Plugin $pluginModel */
        $pluginModel = $em->getRepository(Plugin::class)->findOneBy(['name' => $this->getName()]);

        $aclService->createResource(
            'BucoOrderDocuments',
            ['create', 'read'],
            null,
            $pluginModel ? $pluginModel->getId() : null
        );
    }

    private function removeApiAcls()
    {
        $aclService = $this->container->get('acl');
        $aclService->deleteResource('BucoOrderDocuments');
    }
}