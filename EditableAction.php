<?php
/**
 * @copyright Copyright (c) 2013 2amigOS! Consulting Group LLC
 * @link http://2amigos.us
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace dosamigos\editable;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;

/**
 * EditableAction is a server side Action that helps to update the record in db.
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 * @package dosamigos\editable
 */
class EditableAction extends Action
{
    /**
     * @var string the class name to handle
     */
    public $modelClass;
    /**
     * @var string the scenario to be used (optional)
     */
    public $scenario;
    /**
     * @var \Closure a function to be called previous saving model. The anonymous function is preferable to have the
     * model passed by reference. This is useful when we need to set model with extra data previous update.
     */
    public $preProcess;
    /**
     * @var bool whether to create a model if a primary key parameter was not found.
     */
    public $forceCreate = true;

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if ($this->modelClass === null) {
            throw new InvalidConfigException("'modelClass' cannot be empty.");
        }
    }

    /**
     * Runs the action
     * @return bool
     * @throws \HttpRequestException
     * @throws \HttpInvalidParamException
     */
    public function run()
    {
        $class = $this->modelClass;
        $pk = Yii::$app->request->post('pk');
        $attribute = Yii::$app->request->post('name');
        $value = Yii::$app->request->post('value');
        if ($attribute === null) {
            throw new \HttpInvalidParamException("'name' parameter cannot be empty.");
        }
        if ($value === null) {
            throw new \HttpInvalidParamException("'value' parameter cannot be empty.");
        }
        /** @var \Yii\db\ActiveRecord $model */
        $model = $class::find($pk);
        if (!$model) {
            if ($this->forceCreate) { // only useful for models with one editable attribute or no validations
                $model = new $class;
            } else {
                throw new \HttpRequestException('Entity not found by primary key ' . $pk);
            }
        }
        // do we have a preProcess function
        if ($this->preProcess && is_callable($this->preProcess, true)) {
            call_user_func($this->preProcess, $model);
        }
        if ($this->scenario !== null) {
            $model->setScenario($this->scenario);
        }
        $model->setAttribute($attribute, $value);

        if ($model->validate([$attribute])) {
            // no need to specify which attributes as Yii2 handles that via [[BaseActiveRecord::getDirtyAttributes]]
            return $model->save(false);
        } else {
            throw new \HttpRequestException(Yii::t('app', "Error while saving record!"));
        }
    }

} 