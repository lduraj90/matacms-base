<?php 

namespace matacms\widgets;

use Yii;
use yii\helpers\Json;
use yii\base\Event;
use mata\base\MessageEvent;
use matacms\widgets\Selectize;
use zhuravljov\widgets\DatePicker;
use mata\widgets\DateTimePicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\helpers\Inflector;
use matacms\settings\models\Setting;

class ActiveField extends \yii\widgets\ActiveField {

	public $model;

    const EVENT_INIT_DONE = "matacms\widgets\ActiveField::EVENT_INIT_DONE";

    const SETTING_SHOW_FIELD = "show-field";

    public function init() {
        Event::trigger(self::className(), self::EVENT_INIT_DONE, new MessageEvent($this));
    }
   
    public function render($content = null) {

        if($this->model instanceof \mata\db\ActiveRecord) {
            if (Setting::findValue($this->model->getDocumentId($this->attribute, self::SETTING_SHOW_FIELD)->getIdNoPk()) !== false && 
                Setting::findValue($this->model->getDocumentId($this->attribute, self::SETTING_SHOW_FIELD)->getId()) !== false)
                return parent::render();

            return "";
        }       

        return parent::render();

    }

    public function wysiwyg($options = [])
    {
        $options = array_merge($this->inputOptions, $options);

        if(isset($this->options['class'])) {
            if(strpos($this->options['class'], 'partial-max-width-item') == false)
                $this->options['class'] .= ' full-width-item';
        } else {
            $this->options['class'] = 'full-width-item';
        }

        $options = array_merge([
            "s3" => "/mata-cms/media/redactor/s3",
            "changeCallback" => new JsExpression('function() {mata.form.hasChanged = true;}')
            ], $options);

        $this->adjustLabelFor($options);
        $this->parts['{input}'] = \mata\imperavi\Widget::widget([
            'model' => $this->model,
            'attribute' => $this->attribute,
            'options' => $options,
            'htmlOptions' => [
            'id' => \yii\helpers\Html::getInputId($this->model, $this->attribute)
            ]
            ]);

        return $this;
    }

    public function adjustLabelFor($options) 
    {
        if (isset($options['id']) && !isset($this->labelOptions['for'])) {
            $this->labelOptions['for'] = $options['id'];
        }
    }

    public function dateTime($options = [])
    {

        $options = ArrayHelper::merge([
          'class' => 'form-control',
          ], $options);

        $clientOptions = isset($options["clientOptions"]) ? $options["clientOptions"] : [];

        $attribute = $this->attribute;
        $minDate = (!empty($this->model->$attribute) && $this->model->$attribute < date('Y-m-d H:i')) ? $this->model->$attribute : date('Y-m-d H:i');

        $clientOptions = ArrayHelper::merge([
            'locale' => 'en',
            'format' => 'YYYY-MM-DD HH:mm',
            'minDate' => $minDate,
            'showTodayButton' => true
          ], $clientOptions);

        $this->parts['{input}'] = DateTimePicker::widget([
          'model' => $this->model,
          'attribute' => $this->attribute,
          'options' => $options,
          'clientOptions' => $clientOptions
          ]);

        return $this;
    }

    public function selectize($options = []) 
    {
        $this->parts['{input}'] = Selectize::widget($options);
        return $this;
    }

    public function media($options = []) 
    {
        if(isset($this->options['class'])) {
            $this->options['class'] .= ' partial-max-width-item field-media';
        } else {
            $this->options['class'] = 'partial-max-width-item field-media';
        }
        
        $this->parts['{input}'] = \mata\widgets\fineuploader\FineUploader::widget([
            'model' => $this->model,
            'attribute' => $this->attribute,
            'options' => $options,
            'id' => \yii\helpers\Html::getInputId($this->model, $this->attribute),
            'events' => [
            'complete' => "var inputFileId = '" . \yii\helpers\Html::getInputId($this->model, $this->attribute) . "'; $(this).find('input#" . \yii\helpers\Html::getInputId($this->model, $this->attribute) . "').val(uploadSuccessResponse.DocumentId); mata.form.hasChanged = true;"
            ]
            ]);

        return $this;
    }

    public function autocomplete($items, $options = [])
    {
        if(isset($this->model)) {
            $options['model'] = $this->model;
        }

        if(isset($this->attribute)) {
            $options['attribute'] = $this->attribute;
        }

        $options = ArrayHelper::merge([
            'items' => $items,
            'clientOptions' => ['maxItems' => 1]
            ], $options);

        $this->parts['{input}'] = Selectize::widget($options);
        return $this;
    }

    public function multiselect($items, $options = [])
    {
        $prompt = 'Select ' . Inflector::camel2words($this->attribute);
        if(isset($options['prompt'])) {
            $prompt = $options['prompt'];
            unset($options['prompt']);
        }
        
        if(isset($this->options['class'])) {
            $this->options['class'] .= ' multi-choice-dropdown partial-max-width-item';
        }

        if(isset($this->model) && isset($this->attribute)) {
            $options['name'] = \matacms\helpers\Html::getInputName($this->model, $this->attribute);
        }

        $options = ArrayHelper::merge([
            'items' => $items,
            'options' => ['multiple' => true, 'prompt' => $prompt],
            'clientOptions' => [],
            ], $options);

        $this->parts['{input}'] = Selectize::widget($options);
        return $this;
    }

    public function slug($fieldName, $options = []) 
    {
        if(isset($this->options['class'])) {
            $this->options['class'] .= ' partial-max-width-item';
        } else {
            $this->options['class'] = 'partial-max-width-item';
        }

        $options = ArrayHelper::merge([
            'class' => 'form-control',
            ], $options);

        $this->parts['{input}'] = \matacms\widgets\Slug::widget([
            'model' => $this->model,
            'attribute' => $this->attribute,
            'options' => $options,
            'basedOnAttribute' => $fieldName
            ]);

        return $this;
    }

    public function dropDownList($items, $options = [])
    {

        $prompt = 'Select ' . Inflector::camel2words($this->attribute);
        if(isset($options['prompt'])) {
            $prompt = $options['prompt'];
            unset($options['prompt']);
        }

        if(isset($this->model)) {
            $options['model'] = $this->model;
        }

        if(isset($this->attribute)) {
            $options['attribute'] = $this->attribute;
        }

        if(isset($this->options['class'])) {
            $this->options['class'] .= ' single-choice-dropdown partial-max-width-item';
        } else {
            $this->options['class'] = 'single-choice-dropdown partial-max-width-item';
        }

        $options = ArrayHelper::merge([
            'items' => $items,
            'options' => ['multiple'=>false, 'prompt' => $prompt],
            'clientOptions' => [
            'create' => false,
            'persist' => false,
            ]
            ], $options);

        $this->parts['{input}'] = Selectize::widget($options);

        return $this;
    }
}


