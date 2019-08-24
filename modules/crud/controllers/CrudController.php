<?php
namespace app\modules\crud\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;

use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\behaviors\BreadCrumbsBehavior;

use app\modules\crud\builder\FormBuilder;
use app\modules\crud\builder\GridBuilder;
use app\modules\crud\helpers\ClassI18N;

/**
 * Default controller for the `admin` module
 *
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 */
class CrudController extends Controller {
    public $modelClass;
    public $messageCategory;
    public $modelSearchClass;

    public $addCreateButton = true;

    public $assets = [];

    /**
     * @var \app\modules\crud\builder\GridBuilder
     */
    protected $gridBuilder;

    protected $gridBuilderEvent = [
        GridBuilder::EVENT_BEFORE_BUILD => 'beforeGridBuild',
        GridBuilder::EVENT_AFTER_BUILD => 'afterGridBuild',
        GridBuilder::EVENT_BEFORE_FILTER_APPLY => 'beforeFilterApply',
    ];

    /**
     * @var \app\modules\crud\builder\FormBuilder
     */
    protected $formBuilder;

    protected $formBuilderEvent = [
        FormBuilder::EVENT_BEFORE_BUILD => 'beforeFormBuild',
        FormBuilder::EVENT_AFTER_BUILD => 'afterFormBuild',
    ];

    protected $_useCrudViewPath = true;

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['backUrl'] = BackUrlBehavior::className();
        $behaviors['breadCrumbs'] = BreadCrumbsBehavior::className();

        return $behaviors;
    }

    public function init() {
        parent::init();

        if (!$this->modelClass) {
            throw new InvalidConfigException('Not find model class');
        }

        if (!$this->messageCategory && $this->modelClass) {
            $this->messageCategory = ClassI18N::class2messagesPath($this->modelClass);
        }

        if (!$this->assets) {
            return;
        }

        $view = parent::getView();
        foreach ($this->assets as $asset) {
            $view->registerAssetBundle($asset);
        }
    }

    /**
     * Show model objects list
     * @return string
     */
    public function actionIndex() {
        $view = $this->getView();
        $view->title = Yii::t($this->messageCategory, 'List items');

        $builder = $this->getGridBuilder();
        $builder->build();

        return $this->render('index', compact(['builder']));
    }

    public function getGridBuilder($withCopy = true) {
        if ($this->gridBuilder) {
            return $this->gridBuilder;
        }

        $this->gridBuilder = new GridBuilder();
        if ($withCopy) {
            $this->gridBuilder->controller2this($this);
        }

        foreach ($this->gridBuilderEvent as $event => $method) {
            $this->gridBuilder->on($event, [$this, $method]);
        }

        return $this->gridBuilder;
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'back' url page.
     * @return mixed
     */
    public function actionCreate() {
        return $this->_actionEdit();
    }

    /**
     * Updates an existing model object.
     * If update is successful, the browser will be redirected to the 'back' url page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id) {
        return $this->_actionEdit($id);
    }

    /**
     * Create and edit object nodel
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function _actionEdit($id = null) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();

            return $this->goBack();
        }

        $builder = $this->getFromBuilder();

        foreach ($this->formBuilderEvent as $event => $method) {
            if ($model->hasMethod($method)) {
                $this->formBuilder->on($event, [$model, $method]);
            }
        }

        foreach ($this->formBuilderEvent as $event => $method) {
            if ($this->hasMethod($method)) {
                $this->formBuilder->on($event, [$this, $method]);
            }
        }

        $builder->build($model);

        $view = $this->getView();
        $isCreate = null === $id;
        if ($isCreate) {
            $view->title = Yii::t($this->messageCategory, 'Create item');
        } elseif ($builder->nameAttr) {
            $name = $builder->nameAttr;
            $view->title = Yii::t($this->messageCategory, 'Update item "{nameAttribute}"', ['nameAttribute' => $model->{$name}]);
        } else {
            $view->title = Yii::t($this->messageCategory, 'Update item');
        }

        return $this->render('edit', compact(['model', 'builder']));
    }

    public function getFromBuilder($withCopy = true) {
        if ($this->formBuilder) {
            return $this->formBuilder;
        }

        $this->formBuilder = new FormBuilder();
        if ($withCopy) {
            $this->formBuilder->controller2this($this);
        }

        return $this->formBuilder;
    }

    /**
     * Deletes an existing model object.
     *
     * If deletion is successful, the browser will be redirected to the 'back' url.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id = null) {
        if (null === $id) {
            $selection = Yii::$app->request->post('selection', []);
        } else {
            $selection = [$id];
        }

        $modelClass = $this->modelClass;
        foreach ($modelClass::findAll($selection) as $model) {
            $model->delete();
        }

        return $this->goBack();
    }

    /**
     * Finds the model based on its primary key value.
     *
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return \yii\db\ActiveRecord the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id = null, $exception = true) {
        $modelClass = $this->modelClass;
        if (null === $id) {
            return Yii::createObject($modelClass);
        }

        $model = $modelClass::findOne($id);
        if (null !== $model) {
            return $model;
        }

        if ($exception) {
            throw new NotFoundHttpException('The requested model does not exist.');
        }
    }

    public function getViewPath() {
        $path = parent::getViewPath();
        if (!$this->_useCrudViewPath) {
            return $path;
        }

        $path = str_replace('/' . $this->module->id . '/', '/crud/', $path);
        $path = str_replace('/' . $this->id, '/crud', $path);

        return $path;
    }

    protected function beforeFilterApply() {
    }

    protected function beforeGridBuild() {
    }

    protected function afterGridBuild() {
    }

    protected function beforeFormBuild() {
    }

    protected function afterFormBuild() {
    }
}