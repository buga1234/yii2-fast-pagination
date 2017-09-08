<?php

namespace stesi\fastpagination;

use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\Query;
use yii\db\QueryInterface;
use yii\web\Request;
use Yii;

class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    /**
     * @var int maximum number of page buttons that can be displayed. Defaults to 10.
     */
    public $maxButtonCount = 10;

    /**
     * Returns a value indicating the total number of data models in this data provider.
     *
     * @return int total number of data models in this data provider.
     * @throws InvalidConfigException
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }

        $pagination = $this->pagination;

        $pageSize = $pagination->pageSize;
        $maxButtonCount = $this->maxButtonCount;
        $halfMaxButtonCount = $maxButtonCount / 2;

        $page = (int) $this->getQueryParam($pagination->pageParam, 1) - 1;

        if ($page >= $halfMaxButtonCount) {
            $limit = $page * $pageSize + ($maxButtonCount - $halfMaxButtonCount) * $pageSize + 1;
        } else {
            $limit = $maxButtonCount * $pageSize + 1;
        }

        $query = clone $this->query;
        if (is_callable([$query, 'select'])) {
            $query->select(new Expression('1 as test_field'));
        }
        $query->limit($limit)->offset(-1)->orderBy([]);
        $countQuery = new Query();
        $countQuery->select('COUNT(*)')->from(['stesi_count_query' => $query]);
        return (int) $countQuery->createCommand($this->db)->queryScalar();
    }

    /**
     * Returns the value of the specified query parameter.
     * This method returns the named parameter value from [[params]]. Null is returned if the value does not exist.
     * @param string $name the parameter name
     * @param string $defaultValue the value to be returned when the specified parameter does not exist in [[params]].
     * @return string the parameter value
     */
    protected function getQueryParam($name, $defaultValue = null)
    {
        static $params = null;

        if ($params === null) {
            $request = Yii::$app->getRequest();
            $params = $request instanceof Request ? $request->getQueryParams() : [];
        }

        return isset($params[$name]) && is_scalar($params[$name]) ? $params[$name] : $defaultValue;
    }
}
