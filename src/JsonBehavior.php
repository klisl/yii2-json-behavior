<?php
/**
 * @link https://klisl.com
 * @copyright Klimenchuk Sergey
 */

namespace klisl\behaviors;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * Поведение для автоматической конвертации свойств объектов ActiveRecord в формат JSON и обратно
 * при сохранении данных в БД и при получении данных из нее.
 *
 * Подключение в объекте ActiveRecord:
 * public function behaviors(): array
 * {
 *  return [
 *      [
 *          'class' => JsonBehavior::class,
 *          'property' => 'meta',
 *          'jsonField' => 'meta_json'
 *      ]
 *  ];
 * }
 * Если свойство объекта имеет название идентичное полю в БД, атрибут 'jsonField' можно не указывать.
 *
 * @property string $property Свойство содержащее объект или массив до конвертации в формат JSON
 * @property string $jsonField Поле таблицы для хранения данных в формате JSON
 */
class JsonBehavior extends Behavior
{
    public $property;
    public $jsonField;

    /**
     * Список событий на которые зарегистрировано выполнение указанных методов
     * @return array
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'onAfterFind',
            ActiveRecord::EVENT_BEFORE_INSERT => 'onBeforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'onBeforeSave',
        ];
    }

	public function onAfterFind(Event $event): void
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        $jsonField = $this->getJsonField($model);
        $attribute = $model->getAttribute($jsonField);

        if(!is_array($attribute)){
            $model->{$this->property} = Json::decode($attribute);
        }
    }

    public function onBeforeSave(Event $event): void
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        $jsonField = $this->getJsonField($model);

        $model->setAttribute($jsonField, Json::encode($model->{$this->property}));
    }

    protected function getJsonField(ActiveRecord $model): string
    {
        $jsonField = $this->jsonField ?? $this->property;

        if (!$model->hasAttribute($jsonField)){
            throw new \DomainException("Field $jsonField with type JSON does not exist in the table " . $model::tableName());
        }
        return $jsonField;
    }
}