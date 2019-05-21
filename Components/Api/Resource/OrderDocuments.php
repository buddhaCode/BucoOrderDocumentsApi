<?php

namespace BucoOrderDocumentsApi\Components\Api\Resource;

use BucoOrderDocumentsApi\Components\Repository\OrderDocuments as OrderDocumentsRepo;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Shopware\Components\Api\Exception as ApiException;
use Shopware\Components\Api\Resource\Resource;
use Shopware\Models\Order\Document\Document;

class OrderDocuments extends Resource
{
    /**
     * @var \League\Flysystem\FilesystemInterface
     *
     * Used in Shopware >= 5.5
     */
    private $filesystem;

    /**
     * @var string
     *
     * Used in Shopware < 5.5
     */
    private $docPath;

    public function __construct()
    {
        if($this->getContainer()->has('shopware.filesystem.private')) {
            $this->filesystem = $this->getContainer()->get('shopware.filesystem.private');
        }
        else {
            $this->docPath = $this->getContainer()->getParameter('shopware.app.documentsDir');
        }
    }

    public function getRepository() : EntityRepository
    {
        return new OrderDocumentsRepo($this->getManager(), $this->getManager()->getClassMetadata(Document::class));
    }

    /**
     * @throws ApiException\NotFoundException
     * @throws ApiException\ParameterMissingException
     * @throws ApiException\PrivilegeException
     * @throws NonUniqueResultException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function getOne(int $id, bool $includeDocument = false) : array
    {
        $this->checkPrivilege('read');

        if (empty($id)) {
            throw new ApiException\ParameterMissingException();
        }

        $document = $this
            ->getRepository()
            ->createQueryBuilder('document')
            ->andWhere('document.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult($this->getResultMode());

        if (!$document) {
            throw new ApiException\NotFoundException("Order document by id $id not found");
        }

        if($includeDocument) {
		    $document['pdfDocument'] = $this->getPdfContent($document['hash']);
        }

        return $document;
    }

    public function getList(int $offset = 0, int $limit = 25, array $criteria = [], array $orderBy = []) : array
    {
        $this->checkPrivilege('read');

        $builder = $this->getRepository()->createQueryBuilder('document');

        $builder->addFilter($criteria)
            ->addOrderBy($orderBy)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Set hydration mode
        $query = $builder->getQuery();
        $query->setHydrationMode($this->getResultMode());

        // Get result
        $paginator = $this->getManager()->createPaginator($query);
        $totalResult = $paginator->count();
        $countries = $paginator->getIterator()->getArrayCopy();

        return [
            'data' => $countries,
            'total' => $totalResult,
        ];
    }

    public function delete($id) : \Shopware\Models\Document\Document
    {
        $this->checkPrivilege('delete');

        if (empty($id)) {
            throw new ApiException\ParameterMissingException('id');
        }

        $document = $this->getRepository()->find($id);
        if (!$document) {
            throw new ApiException\NotFoundException(sprintf('Order document by id %d not found', $id));
        }

        $this->getManager()->remove($document);
        $this->flush();

        return $document;
    }

    /**
     * @return string|null
     */
	protected function getPdfContent(string $documentHash)
	{
        return $this->filesystem
            ? $this->getPdfContentViaFal($documentHash)
            : $this->getPdfContentViaLocalFileSystem($documentHash);
	}

    /**
     * Access file via file abstraction layer
     *
     * @return string|null
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function getPdfContentViaFal(string $documentHash)
    {
        $file = $this->getRelativePdfFilePath($documentHash);

        return $this->filesystem->has($file)
            ? base64_encode($this->filesystem->read($file))
            : null;
    }

    /**
     * Access file directly
     *
     * @param string $documentHash
     *
     * @return string|null
     */
    protected function getPdfContentViaLocalFileSystem(string $documentHash)
    {
        $file = $this->getAbsoultePdfFilePath($documentHash);

        return file_exists($file)
            ? base64_encode(file_get_contents($file))
            : null;
    }

    /**
     * @param int $id
     *
     * @return \resource|null
     *
     * @throws ApiException\NotFoundException
     * @throws ApiException\ParameterMissingException
     * @throws ApiException\PrivilegeException
     * @throws NonUniqueResultException
     */
	public function getPdfStream(int $id)
    {
        $doc = $this->getOne($id);

        if($this->filesystem) {
            $file = $this->getRelativePdfFilePath($doc['hash']);
            return $this->getPdfStreamViaFal($file);
        }
        else {
            $file = $this->getAbsoultePdfFilePath($doc['hash']);
            return $this->getPdfStreamViaLocalFileSystem($file);
        }
    }

    /**
     * @return \resource|null
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function getPdfStreamViaFal(string $file)
    {
        return $this->filesystem->has($file)
            ? $this->filesystem->readStream($file)
            : null;
    }

    /**
     * @return \resource|null
     */
    public function getPdfStreamViaLocalFileSystem(string $file)
    {
        return file_exists($file)
            ? fopen($file, 'r')
            : null;
    }

    private function getRelativePdfFilePath(string $documentHash) : string
    {
        return "documents/{$documentHash}.pdf";
    }

    private function getAbsoultePdfFilePath(string $documentHash) : string
    {
        return "{$this->docPath}/{$documentHash}.pdf";
    }
}