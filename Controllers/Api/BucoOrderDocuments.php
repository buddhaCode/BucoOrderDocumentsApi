<?php

use BucoOrderDocumentsApi\Components\Api\Resource\OrderDocuments;
use Shopware\Components\Api\Manager;
use Symfony\Component\HttpFoundation\AcceptHeader;

class Shopware_Controllers_Api_BucoOrderDocuments extends Shopware_Controllers_Api_Rest
{
    /** @var OrderDocuments */
    protected $resource = null;

    public function init()
    {
        $this->resource = Manager::getResource('BucoOrderDocuments');
    }

    /**
     * GET /api/BucoOrderDocuments/
     */
    public function indexAction()
    {
        $limit  = $this->Request()->getParam('limit', 1000);
        $offset = $this->Request()->getParam('start', 0);
        $sort = $this->Request()->getParam('sort', array());
        $filter = $this->Request()->getParam('filter', array());

        $result = $this->resource->getList($offset, $limit, $filter, $sort);

        $this->View()->assign($result);
        $this->View()->assign('success', true);
    }

    /**
     * GET /api/BucoOrderDocuments/{id}
     *
     * Accept: application/pdf --> returns pdf document
     * Accept: <all other> --> returns JSON encoded meta data with embedded base64 encoded document content
     */
    public function getAction()
    {
        $acceptHeader = AcceptHeader::fromString($this->Request()->getHeader('Accept'));

        if ($acceptHeader->has('application/pdf')) {
            $this->forward('getAsPdf');
        }

        $this->forward('getAsJson');
    }

    protected function getAsJsonAction()
    {
        $id = $this->Request()->getParam('id');

        $document = $this->resource->getOne($id, true);

        $this->View()->assign('data', $document);
        $this->View()->assign('success', true);
    }

    protected function getAsPdfAction()
    {
        $id = $this->Request()->getParam('id');
        $pdfStream = $this->resource->getPdfStream($id);
        $fileSize = fstat($pdfStream)['size'];

        // Disable Smarty rendering
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Front()->Plugins()->Json()->setRenderer(false);
//        $this->View()->clearAssign();
//        $this->View()->Engine()->clearAllAssign();

        $response = $this->Response();
        $response->clearBody();
        $response->setHeader('Cache-Control', 'public');
        $response->setHeader('Content-Type', 'application/pdf');
        $response->setHeader('Content-Transfer-Encoding', 'binary');
        $response->setHeader('Content-Length', $fileSize);
        $response->sendHeaders();
        $response->sendResponse();

        fpassthru($pdfStream);

        // We don't want JSON output produced by the postDispatch() method. Even if we're flushing the template vars,
        // there will be an "[]" in the output. As there is no way to prevent this, we're quiting the hard way.
        die();
    }

    /**
     * DELETE /api/BucoOrderDocuments/{id}
     */
    public function deleteAction()
    {
        $id = $this->Request()->getParam('id');

        $this->resource->delete($id);

        $this->View()->assign('success', true);
    }
}
