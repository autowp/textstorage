<?php

namespace Autowp\TextStorage;

use Autowp\TextStorage\Exception;

use Zend_Db_Adapter_Abstract;
use Zend_Db_Expr;
use Zend_Db_Table;
use Zend_Db_Table_Abstract;

class Service
{

    /**
     * Zend_Db_Adapter_Abstract object.
     *
     * @var Zend_Db_Adapter_Abstract
     */
    private $db = null;

    /**
     * @var Zend_Db_Table
     */
    private $textTable = null;

    /**
     * @var Zend_Db_Table
     */
    private $revisionTable = null;

    /**
     * @var string
     */
    private $textTableName = 'textstorage_text';

    /**
     * @var string
     */
    private $revisionTableName = 'textstorage_revision';

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @return Service
     * @throws Exception
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                !$this->raise("Unexpected option '$key'");
            }
            
            $this->$method($value);
        }

        return $this;
    }

    /**
     * @param string $message
     * @throws Exception
     */
    private function raise($message)
    {
        throw new Exception($message);
    }

    /**
     * @param Zend_Db_Adapter_Abstract $dbAdapter
     * @return TextStorage
     */
    public function setDbAdapter(Zend_Db_Adapter_Abstract $dbAdapter)
    {
        $this->db = $dbAdapter;

        return $this;
    }

    /**
     * @param string $name
     * @return TextStorage
     */
    public function setTextTableName($name)
    {
        $this->textTableName = (string)$name;

        return $this;
    }

    /**
     * @param string $name
     * @return TextStorage
     */
    public function setRevisionTableName($name)
    {
        $this->revisionTableName = (string)$name;

        return $this;
    }

    /**
     * @return Zend_Db_Table
     */
    private function getTextTable()
    {
        if (null === $this->textTable) {
            $this->textTable = new Zend_Db_Table(array(
                Zend_Db_Table_Abstract::ADAPTER => $this->db,
                Zend_Db_Table_Abstract::NAME    => $this->textTableName,
            ));
        }

        return $this->textTable;
    }

    /**
     * @return Zend_Db_Table
     */
    private function getRevisionTable()
    {
        if (null === $this->revisionTable) {
            $this->revisionTable = new Zend_Db_Table(array(
                Zend_Db_Table_Abstract::ADAPTER => $this->db,
                Zend_Db_Table_Abstract::NAME    => $this->revisionTableName,
            ));
        }

        return $this->revisionTable;
    }

    public function getText($id)
    {
        $row = $this->getTextTable()->fetchRow(array(
            'id = ?' => (int)$id
        ));

        if ($row) {
            return $row->text;
        } else {
            return null;
        }
    }

    public function getTextInfo($id)
    {
        $row = $this->getTextTable()->fetchRow(array(
            'id = ?' => (int)$id
        ));

        if ($row) {
            return array(
                'text'     => $row->text,
                'revision' => $row->revision
            );
        } else {
            return null;
        }
    }

    public function getRevisionInfo($id, $revision)
    {
        $row = $this->getRevisionTable()->fetchRow(array(
            'text_id = ?' => (int)$id,
            'revision =?' => (int)$revision
        ));

        if ($row) {
            return array(
                'text'     => $row->text,
                'revision' => $row->revision,
                'user_id'  => $row->user_id
            );
        } else {
            return null;
        }
    }

    public function setText($id, $text, $userId)
    {
        $row = $this->getTextTable()->fetchRow(array(
            'id = ?' => (int)$id
        ));

        if (!$row) {
            return $this->raise('Text `' . $id . '` not found');
        }

        if ($row->text != $text) {

            $row->setFromArray(array(
                'revision'     => new Zend_Db_Expr('revision + 1'),
                'text'         => $text,
                'last_updated' => new Zend_Db_Expr('NOW()')
            ));
            $row->save();

            $revisionRow = $this->getRevisionTable()->createRow(array(
                'text_id'   => $row->id,
                'revision'  => $row->revision,
                'text'      => $row->text,
                'timestamp' => $row->last_updated,
                'user_id'   => $userId
            ));
            $revisionRow->save();
        }

        return $row->id;
    }

    public function createText($text, $userId)
    {
        $row = $this->getTextTable()->createRow(array(
            'revision'     => 0,
            'text'         => '',
            'last_updated' => new Zend_Db_Expr('NOW()')
        ));
        $row->save();

        return $this->setText($row->id, $text, $userId);
    }

    public function getTextUserIds($id)
    {
        $table = $this->getRevisionTable();
        $db = $table->getAdapter();
        return $db->fetchCol(
            $db->select()
                ->from($table->info('name'), 'user_id')
                ->where('user_id')
                ->where('text_id = ?', (int)$id)
        );
    }
}