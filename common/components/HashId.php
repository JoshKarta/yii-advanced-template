<?php

namespace common\components;

use Hashids\Hashids;
use Yii;
use yii\base\Component;

class HashId extends Component
{
    /**
     * @var string The salt (secret) used for encoding.
     */
    public $salt;

    /**
     * @var int Minimum length of the generated hash.
     */
    public $minHashLength = 8; // fallback default

    /**
     * @var Hashids|null The Hashids instance (cached).
     */
    private $_hashids;

    /**
     * Initializes the component.
     */
    public function init()
    {
        parent::init();

        // Set salt from params or fallback
        if (empty($this->salt)) {
            $this->salt = Yii::$app->params['hashids']['salt'] ?? 'default-salt';
        }

        // Override minHashLength from params if set
        if (isset(Yii::$app->params['hashids']['minHashLength'])) {
            $this->minHashLength = Yii::$app->params['hashids']['minHashLength'];
        }
    }

    /**
     * Returns the Hashids instance.
     *
     * @return Hashids
     */
    protected function getHashids()
    {
        if ($this->_hashids === null) {
            $this->_hashids = new Hashids($this->salt, $this->minHashLength);
        }
        return $this->_hashids;
    }

    /**
     * Encodes a numeric ID (or array of IDs) into a hash string.
     *
     * @param int|array $id
     * @return string
     */
    public function encode($id)
    {
        return $this->getHashids()->encode($id);
    }

    /**
     * Decodes a hash string back to the original ID(s).
     *
     * @param string $hash
     * @return int|array|null
     */
    public function decode($hash)
    {
        $result = $this->getHashids()->decode($hash);
        if (empty($result)) {
            return null;
        }
        return count($result) === 1 ? $result[0] : $result;
    }
}