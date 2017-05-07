<?php
/**
 * @author Vadim Trunov <vadim.tr@bigdropinc.com>
 *
 * @copyright (C) 2017 - Bigdrop Inc
 *
 * @license https://opensource.org/licenses/BSD-3-Clause
 */

namespace bigdropinc\sti;

class ActiveQuery extends \yii\db\ActiveQuery
{
    private $stiColumn;
    private $stiValue;
    private $isStiConditionEnabled;

    /**
     * @inheritdoc
     */
    public function prepare($builder)
    {
        $this->withSti();
        return parent::prepare($builder);
    }

    /**
     * Call this method if you don't want to use STI condition for specific query instance
     *
     * @return $this
     */
    final public function withoutSti()
    {
        $this->isStiConditionEnabled = false;
        return $this;
    }

    /**
     * You may want to override this method if you want paste specific logic on STI query condition
     *
     * @return $this
     */
    protected function withSti()
    {
        if($this->isStiConditionEnabled){
            $column = call_user_func([$this->modelClass, 'tableName']).'.'.$this->stiColumn;
            $this->andWhere([$column => $this->stiValue]);
        }
        return $this;
    }



    public function setStiColumn($column)
    {
        $this->stiColumn = $column;
    }

    public function setStiValue($value)
    {
        $this->stiValue = $value;
    }

    public function setIsStiConditionEnabled($isEnabled)
    {
        $this->isStiConditionEnabled = $isEnabled;
    }

}