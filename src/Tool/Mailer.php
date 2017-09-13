<?php
/**
 * w-vision
 *
 * LICENSE
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that is distributed with this source code.
 *
 * @copyright  Copyright (c) 2017 Woche-Pass AG (https://www.w-vision.ch)
 */

namespace WvisionBundle\Tool;

use Pimcore\Mail;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Service\Request\DocumentResolver;
use Pimcore\Service\Request\ViewModelResolver;
use Symfony\Component\HttpFoundation\Request;

class Mailer
{
    /**
     * @var DocumentResolver
     */
    private $documentResolver;

    /**
     * @var array
     */
    private $emails = [];

    /**
     * @var array
     */
    private $documents = [];

    /**
     * @var array
     */
    private $assets = [];

    /**
     * @var bool
     */
    private $success = false;

    /**
     * @param ViewModelResolver $viewModel
     */
    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    /**
     * @return array
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param array|string $emails
     */
    public function setEmails($emails)
    {
        if (is_string($emails)) {
            array_push($this->emails, $emails);
        } else {
            $this->emails = $emails;
        }
    }

    /**
     * @return array
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param array|Document $documents
     */
    public function setDocuments($documents)
    {
        if ($documents instanceof Document\Email) {
            array_push($this->documents, $documents);
        } else {
            $this->documents = $documents;
        }
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param array|Asset $assets
     */
    public function setAssets($assets)
    {
        if ($assets instanceof Asset) {
            array_push($this->assets, $assets);
        } else {
            $this->assets = $assets;
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     * @param $data
     * @param array $adminEmail
     * @return bool
     */
    public function sendEmails($data, $adminEmail = [])
    {
        $admin = $this->parseData($data, $adminEmail);

        if (!empty($this->getDocuments())) {
            $success = false;
            foreach ($this->getDocuments() as $document) {
                $success = $this->send($this->getEmails(), $data, $document, $this->getAssets());

                if (!$success) {
                    continue;
                }
            }

            $this->setSuccess($success);
        }

        if (!empty($admin) && !empty($admin['documents'])) {
            $success = false;
            foreach ($admin['documents'] as $document) {
                $success = $this->send($admin['emails'], $adminEmail, $document, $admin['assets']);

                if (!$success) {
                    continue;
                }
            }

            $this->setSuccess($success);
        }

        return $this->isSuccess();
    }

    /**
     * @param $data
     * @param $admin
     * @return array
     */
    public function parseData($data, $admin)
    {
        $document = $this->documentResolver->getDocument();

        foreach ($data as $param) {
            if (is_string($param) && Mail::isValidEmailAddress($param)) {
                $this->setEmails($param);
            }
            else if ($param instanceof Document\Email) {
                $this->setDocuments($param);
            }
            else if ($param instanceof Asset) {
                $this->setAssets($param);
            }
        }

        // Additionally add document from properties
        $userEmail = $document->getProperty('userEmailDocument');
        $this->setDocuments($userEmail);

        if (!empty($admin)) {
            $adminArray = [];
            foreach ($admin as $param) {
                $adminArray['emails'] = [];
                
                if (is_string($param) && Mail::isValidEmailAddress($param)) {
                    $adminArray['emails'][] = $param;
                }

                $adminArray['documents'] = [];
                if ($param instanceof Document\Email) {
                    $adminArray['documents'][] = $param;
                }

                $adminArray['assets'] = [];
                if ($param instanceof Asset) {
                    $adminArray['assets'][] = $param;
                }
            }

            // Additionally add document from properties
            $adminArray['documents'][] = $document->getProperty('adminEmailDocument');

            return $adminArray;
        }

        return [];
    }

    /**
     * Sends an email to one or multiple addresses
     * with or without attachment(s).
     *
     * @param $emails
     * @param $params
     * @param Document\Email $document
     * @param array $assets
     * @return bool
     */
    public function send($emails, array $params, Document\Email $document, array $assets)
    {
        $mail = new Mail();
        $mail->addTo($emails);
        $mail->setDocument($document);
        $mail->setParams($params);

        if (!empty($assets)) {
            foreach ($assets as $asset) {
                $mail->createAttachment($asset->getData(), $asset->getMimetype(), $asset->getFilename());
            }
        }

        $sentEmail = $mail->send();

        if ($sentEmail instanceof Mail) {
            return true;
        }

        return false;
    }
}