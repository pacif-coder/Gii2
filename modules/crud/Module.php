<?php
namespace app\modules\crud;

/**
 * CRUD module definition class
 */
class Module extends \yii\base\Module {
    public $defaultRoute = 'crud';

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\crud\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();

        // custom initialization code goes here
    }
}