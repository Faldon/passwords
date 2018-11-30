<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class Share
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getSourcePassword()
 * @method void setSourcePassword(string $sourcePassword)
 * @method string getTargetPassword()
 * @method void setTargetPassword(string $targetPassword)
 * @method string getReceiver()
 * @method void setReceiver(string $receiver)
 * @method bool getEditable()
 * @method void setEditable(bool $editable)
 * @method bool getShareable()
 * @method void setShareable(bool $shareable)
 * @method bool getSourceUpdated()
 * @method bool getTargetUpdated()
 * @method int getExpires()
 * @method void setExpires(int $expires)
 *
 * @package OCA\Passwords\Db
 */
class Share extends AbstractEntity implements EntityInterface {

    const TYPE_USER  = 'user';
    const TYPE_GROUP = 'group';
    const TYPE_LINK  = 'link';

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $receiver;

    /**
     * @var bool
     */
    protected $editable;

    /**
     * @var bool
     */
    protected $shareable;

    /**
     * @var int
     */
    protected $expires;

    /**
     * @var string
     */
    protected $sourcePassword;

    /**
     * @var string
     */
    protected $targetPassword;

    /**
     * @var bool
     */
    protected $sourceUpdated;

    /**
     * @var bool
     */
    protected $targetUpdated;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('type', 'string');
        $this->addType('receiver', 'string');
        $this->addType('sourcePassword', 'string');
        $this->addType('targetPassword', 'string');

        $this->addType('expires', 'integer');

        $this->addType('editable', 'boolean');
        $this->addType('shareable', 'boolean');
        $this->addType('sourceUpdated', 'boolean');
        $this->addType('targetUpdated', 'boolean');

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function isEditable(): bool {
        return $this->getEditable() === true;
    }

    /**
     * @return bool
     */
    public function isShareable(): bool {
        return $this->getShareable() === true;
    }

    /**
     * @return bool
     */
    public function isSourceUpdated(): bool {
        return $this->getSourceUpdated() === true;
    }

    /**
     * @param bool $sourceUpdated
     */
    public function setSourceUpdated(bool $sourceUpdated) {
        $this->sourceUpdated = $sourceUpdated === true;
        $this->markFieldUpdated('sourceUpdated');
    }

    /**
     * @return bool
     */
    public function isTargetUpdated(): bool {
        return $this->getTargetUpdated() === true;
    }

    /**
     * @param bool $targetUpdated
     */
    public function setTargetUpdated(bool $targetUpdated) {
        $this->targetUpdated = $targetUpdated === true;
        $this->markFieldUpdated('targetUpdated');
    }
}