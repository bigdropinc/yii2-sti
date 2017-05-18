<?php
/**
 * @author Vadim Trunov <vadim.tr@bigdropinc.com>
 *
 * @copyright (C) 2017 - Bigdrop Inc
 *
 * @license https://opensource.org/licenses/BSD-3-Clause
 */

namespace bigdropinc\sti;


use Yii;
use yii\base\Exception;
use yii\helpers\StringHelper;

class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * Convert one STI class to another. Returns a converted model.
     *
     * Pay ATTENTION that this method do not change current instance.
     * You should use model that returns by this method.
     *
     * Save changes if flag [[$needSave]] turned to true
     *
     * @param $class
     * @param bool $needSave
     * @return static
     * @throws Exception
     */
    public function becomes($class, $needSave = true)
    {
        if (static::isStiEnabled() && class_exists($class)) {
            $value = static::isBaseClass($class) ? null : static::getStiValue($class);
            $this->setAttribute(static::getStiColumn(), $value);

            /**
             * @var ActiveRecord $model
             */
            $model = call_user_func([$class, 'instantiate'], $this->attributes);
            $model->setAttributes($this->attributes, false);

            if ($model->validate()) {
                if($needSave){
                    if ($this->updateAttributes([static::getStiColumn() => $value])) {
                        $model->setOldAttributes($this->getOldAttributes());
                    } else {
                        throw new Exception('Error during STI column save');
                    }
                }
            } else {
                throw new Exception('Model not satisfied validation rules of model it becomes');
            }
            return $model;
        } else {
            throw new Exception('Can not became to "' . $class . '". Please check STI settings for "' . static::class . '"');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->setStiColumn(static::getStiValue());
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        $query = Yii::createObject(static::getActiveQuery(), [get_called_class(), static::getQueryConfiguration()]);
        if (!static::isActiveQueryClassSupportSti($query)) {
            throw new Exception('Your ActiveQuery should extends sti/ActiveQuery');
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public static function instantiate($row)
    {
        if (isset($row[static::getStiColumn()])) {
            $className = (new \ReflectionClass(static::class))->getNamespaceName() . '\\' . $row[static::getStiColumn()];
            if (class_exists($className)) {
                return new $className;
            }
        }
        return parent::instantiate($row);
    }

    /**
     * Returns a class-name string of a ActiveQuery, that use in [[find()]] method.
     *
     * You may override this method if need a specific ActiveQuery for find record.
     *
     * IMPORTANT: Your ActiveQuery should extend from sti/ActiveQuery
     */
    protected static function getActiveQuery()
    {
        return ActiveQuery::className();
    }

    /**
     * Returns name of sti column in database
     *
     * Override this method if sti column different from "type"
     */
    protected static function getStiColumn()
    {
        return 'type';
    }

    /**
     * Returns sti value that paste into a database.
     *
     * You may override this method if prefer a specific sti value different from class name
     */
    protected static function getStiValue($className = null)
    {
        $className = $className ? $className : static::class;
        return self::getShortClassName($className);
    }

    /**
     * Returns true if STI enabled.
     *
     * You may want to override this method to brake STI in specific child model.
     * If returns false the STI will not pass to the database and not check on populating.
     *
     * Also pay attention that if you only need to disable STI query condition
     * you may want to use method withoutSti in [[sti/ActiveQuery]]
     *
     * @return bool
     */
    protected static function isStiEnabled()
    {
        return true;
    }

    /**
     * Returns configuration array for STI [[ActiveQuery]]
     *
     * @return array
     */
    protected static function getQueryConfiguration()
    {
        return [
            'stiColumn' => static::getStiColumn(),
            'stiValue' => static::getStiValue(),
            'isStiConditionEnabled' => static::isStiNecessary()
        ];
    }

    protected function setStiColumn($value)
    {
        if (static::isStiNecessary()) {
            $this->setAttribute($this->stiColumn, $value);
        }
    }

    /**
     * Check that STI column have or should have not null value
     *
     * @return bool
     */
    protected static function isStiNecessary()
    {
        return static::isStiEnabled() &&
            static::isStiColumnPresentInDatabase() &&
            !static::isBaseClass(static::class);
    }


    protected static function isStiColumnPresentInDatabase()
    {
        $table = Yii::$app->db->schema->getTableSchema(static::tableName());
        return isset($table->columns[static::getStiColumn()]);
    }
    
    protected static function isActiveQueryClassSupportSti($activeQuery)
    {
        $baseActiveQuery = self::getActiveQuery();
        return is_subclass_of($activeQuery, $baseActiveQuery) || $activeQuery instanceof $baseActiveQuery;
    }

    /**
     * Check that class is a root node in STI tree. So it STI column should set to NULL.
     * 
     * @param $class
     * @return bool
     */
    protected static function isBaseClass($class)
    {
        $parentClass = get_parent_class($class);

        $className = self::getShortClassName($class);
        $parentClassName = self::getShortClassName($parentClass);
        if ($className == $parentClassName) {
            return static::isBaseClass($parentClass);
        } elseif ($parentClassName == 'ActiveRecord') {
            return true;
        } else {
            return false;
        }
    }

    private static function getShortClassName($class)
    {
        return StringHelper::basename($class);
    }

}