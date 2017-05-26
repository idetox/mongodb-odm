<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * This factory is used to create default repository objects for entities at runtime.
 */
class DefaultRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of DocumentRepository instances.
     *
     * @var array<\Doctrine\Common\Persistence\ObjectRepository>
     */
    private $repositoryList = array();

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata = $documentManager->getClassMetadata($documentName);
        $hashKey = $metadata->getName() . spl_object_hash($documentManager);

        if (isset($this->repositoryList[$hashKey])) {
            return $this->repositoryList[$hashKey];
        }

        $repository = $this->createRepository($documentManager, ltrim($documentName, '\\'));

        $this->repositoryList[$hashKey] = $repository;

        return $repository;
    }

    /**
     * Create a new repository instance for a document class.
     *
     * @param DocumentManager $documentManager The DocumentManager instance.
     * @param string          $documentName    The name of the document.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function createRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata            = $documentManager->getClassMetadata($documentName);
        $repositoryClassName = $metadata->customRepositoryClassName;

        if ($repositoryClassName === null) {
            $configuration       = $documentManager->getConfiguration();
            $repositoryClassName = $configuration->getDefaultRepositoryClassName();
        }

        return new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
    }
}