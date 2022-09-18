<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\elasticsearch7;

use yii\base\NotSupportedException;

/**
 * QueryBuilder builds an elasticsearch query based on the specification given as a [[Query]] object.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class QueryBuilder extends \yii\base\Object
{
    /**
     * @var Connection the database connection.
     */
    public $db;

    public $should = [];

    /**
     * Constructor.
     * @param Connection $connection the database connection.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($connection, $config = [])
    {
        $this->db = $connection;
        parent::__construct($config);
    }

    /**
     * Generates query from a [[Query]] object.
     * @param Query $query the [[Query]] object from which the query will be generated
     * @return array the generated SQL statement (the first array element) and the corresponding
     * parameters to be bound to the SQL statement (the second array element).
     */
    public function build($query)
    {
        $parts = [];

        if ($query->fields === []) {
            $parts['fields'] = [];
        } elseif ($query->fields !== null) {
            $fields = [];
            foreach ($query->fields as $key => $field) {
                if (is_int($key)) {
                    $fields[] = $field;
                }
            }
            if (!empty($fields)) {
                $parts['fields'] = $fields;
            }
        }
        if ($query->source !== null) {
            $parts['_source'] = $query->source;
        }
        if ($query->limit !== null && $query->limit >= 0) {
            $parts['size'] = $query->limit;
        }
        if ($query->offset > 0) {
            $parts['from'] = (int)$query->offset;
        }
        if (isset($query->minScore)) {
            $parts['min_score'] = (float)$query->minScore;
        }
        if (isset($query->explain)) {
            $parts['explain'] = $query->explain;
        }

        $whereFilter = $this->buildCondition($query->where);
        if ($whereFilter){
            $list = [];
            $this->buildFinalCondition($whereFilter,$list);
            $parts['query']['bool'] = $list;
        }
        if (!empty($query->query) && is_array($query->query)){
            $parts['query']['bool']['must'][] =  $query->query;
        }elseif (!empty($query->query)) {
            //截取200个字符,es查询字符串长度限制最大1200字符
            $keyword = mb_substr($query->query,0,200);

            ###替换违禁词start,临时解决问题，所以加的这坨代码
            $badWords = ['黑客','木马','攻击','渗透','入侵','拖库','脱裤','梯子','VPN','SSR','酸酸乳','自由门','无界','赛风','攻防','翻墙'];
            $keyword = str_ireplace($badWords, 'sflkjsldkfjlskadf', $keyword);
            ###替换违禁词end

            $parts['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => $query->queryKeywordFields,
                    'type' => $query->isExactMatch ? 'phrase' : 'best_fields'
                    //匹配方式：best_fields：模糊匹配,如果字段指定分词则会分词 , phrase：精确匹配，如果字段指定分词则不分词
                ]
            ];
        }

		//filter
		if (!empty($query->filter)) {
			$filter = [];
			foreach ($query->filter as $x => $y) {
				foreach ($y as $k => $v) {
					if (!is_array($v)) {
						//term
						$filter[] = [
							'term' => [
								"$k" => [
									'value' => $v
								]
							]
						];
					} else {
						//terms
						$filter[] = [
							'terms' => [
								"$k" => $v
							]
						];
					}
				}
			}

			$parts['query']['bool']['filter'] = $filter;
		}


        if(!empty($this->should)){
            $parts['query']['bool']['should'] = $this->should;
            $parts['query']['bool']['minimum_should_match'] = 1;
        }
        if(!$whereFilter && !$query->query && !$query->filter)
            $parts['query'] = ["match_all" => (object)[]];

        if (!empty($query->highlight)) {
            $HighlightFields = [];
            foreach ($query->highlight as $field) {
                $HighlightFields[$field] = (object) ['number_of_fragments'=>0];
            }
            $parts['highlight']['fields'] = $HighlightFields;
        }
        if (!empty($query->aggregations)) {
            $parts['aggregations'] = $query->aggregations;
        }
        if (!empty($query->stats)) {
            $parts['stats'] = $query->stats;
        }
        if (!empty($query->suggest)) {
            $parts['suggest'] = $query->suggest;
        }
        if (!empty($query->postFilter)) {
            $parts['post_filter'] = $query->postFilter;
        }

        $sort = $this->buildOrderBy($query->orderBy);
        if (!empty($sort)) {
            $parts['sort'] = $sort;
            #When sorting on a field, scores are not computed.
            #By setting track_scores to true, scores will still be computed and tracked
            $parts['track_scores'] = true;
        }
        if (isset($query->trackScores)) {
			$parts['track_scores'] = $query->trackScores;
		}

        $options = $query->options;
        if ($query->timeout !== null) {
            $options['timeout'] = $query->timeout;
        }

        return [
            'queryParts' => $parts,
            'index' => $query->index,
            'type' => $query->type,
            'options' => $options,
        ];
    }

    /**
     * adds order by condition to the query
     */
    public function buildOrderBy($columns)
    {
        if (empty($columns)) {
            return [];
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            if (is_string($direction)) {
                $column = $direction;
                $direction = SORT_ASC;
            } else {
                $column = $name;
            }
            if ($column == '_id') {
                $column = '_uid';
            }
            // allow elasticsearch extended syntax as described in https://www.elastic.co/guide/en/elasticsearch/reference/5.5/search-request-sort.html
            //自定义排序
            if($name == '_script'){
                $orders['_script'] = $direction;
            }else{
                if (is_array($direction)) {
                    $orders[] = [$column => ['order'=>$direction]];
                } else {
                    $orders[] = [$column => ['order'=>($direction === SORT_DESC ? 'desc' : 'asc')]];
                }
            }
        }

        return $orders;
    }

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param string|array $condition the condition specification. Please refer to [[Query::where()]] on how to specify a condition.
     * @throws \yii\base\InvalidParamException if unknown operator is used in query
     * @throws \yii\base\NotSupportedException if string conditions are used in where
     * @return string the generated SQL expression
     */
    public function buildCondition($condition)
    {
        static $builders = [
            'in' => 'buildInCondition',
            'and' => 'buildAndCondition',
            '<' => 'buildHalfBoundedRangeCondition',
            '>' => 'buildHalfBoundedRangeCondition',
            '>=' => 'buildHalfBoundedRangeCondition',
            '<=' => 'buildHalfBoundedRangeCondition',
            'lt' => 'buildHalfBoundedRangeCondition',
            'lte' => 'buildHalfBoundedRangeCondition',
            'gt' => 'buildHalfBoundedRangeCondition',
            'gte' => 'buildHalfBoundedRangeCondition',
            'or' => 'buildShould',
            'not in' => 'buildInCondition',
            '!=' => 'buildInCondition',
            /*  下面几种查询暂不支持
            'not' => 'buildNotCondition',
            'between' => 'buildBetweenCondition',
            'not between' => 'buildBetweenCondition',
            'not in' => 'buildInCondition',
            'like' => 'buildLikeCondition',
            'not like' => 'buildLikeCondition',
            'or like' => 'buildLikeCondition',
            'or not like' => 'buildLikeCondition',
            */
        ];

        if (empty($condition)) {
            return [];
        }
        if (!is_array($condition)) {
            throw new NotSupportedException('String conditions in where() are not supported by elasticsearch.');
        }
        if (isset($condition[0])) { // operator format: operator, operand 1, operand 2, ...
            $operator = strtolower($condition[0]);
            if (isset($builders[$operator])) {
                $method = $builders[$operator];
                array_shift($condition);

                return $this->$method($operator, $condition);
            } else {
                throw new InvalidParamException('Found unknown operator in query: ' . $operator);
            }
        } else { // hash format: 'column1' => 'value1', 'column2' => 'value2', ...

            return $this->buildHashCondition($condition);
        }
    }

    /**
     * 递归获取es查询条件
     * @param $data
     * @return array|void
     */
    public function buildFinalCondition($data,&$list){
        if($data){
            foreach ($data as $key=>$val){
                if (is_numeric($key) || $key == 'in') {
                    $this->buildFinalCondition($val,$list);
                }elseif($key == 'not in' ){

                    if(is_array($val)){
                        $list['must_not'][]['terms'] =
                            $val
                        ;
                    }
                }elseif($key == '!=' ){

                    if(is_array($val)){
                        $list['must_not'][]['term'] =
                            $val
                        ;
                    }
                } elseif($key == 'range'){
                    $list['must'][] = [
                        $key=>$val
                    ];
                } else {
                    if(is_array($val))
                        $list['must'][]['terms'] = [
                            $key=>$val
                        ];
                    else{
                        $list['must'][]['term'] = [
                            $key=>$val
                        ];
                    }
                }
            }
        }
    }

    private function buildHashCondition($condition)
    {
        $parts = [];
        foreach ($condition as $attribute => $value) {
            $parts[$attribute] = $value;
            //$parts['must'][]['term'][$attribute] = $value;
        }
        return $parts;
        //return count($parts) === 1 ? $parts[0] : ['must' => $parts];
    }

    private function buildNotCondition($operator, $operands)
    {
        if (count($operands) != 1) {
            throw new InvalidParamException("Operator '$operator' requires exactly one operand.");
        }

        $operand = reset($operands);
        if (is_array($operand)) {
            $operand = $this->buildCondition($operand);
        }

        return [$operator => $operand];
    }

    private function buildAndCondition($operator, $operands)
    {
        $parts = [];
        foreach ($operands as $operand) {
            if (is_array($operand)) {
                $operand = $this->buildCondition($operand);
            }
            if (!empty($operand)) {
                $parts[] = $operand;
            }
        }
        if (!empty($parts)) {
            return $parts;
        } else {
            return [];
        }
    }

    private function buildBetweenCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidParamException("Operator '$operator' requires three operands.");
        }

        list($column, $value1, $value2) = $operands;
        if ($column == '_id') {
            throw new NotSupportedException('Between condition is not supported for the _id field.');
        }
        $filter = ['range' => [$column => ['gte' => $value1, 'lte' => $value2]]];
        if ($operator == 'not between') {
            $filter = ['not' => $filter];
        }

        return $filter;
    }

    private function buildInCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidParamException("Operator '$operator' requires two operands.");
        }

        list($column, $values) = $operands;

        //$parts['must'][]['terms'][$column] = $values;
        $parts[$operator][$column] = $values;
        return $parts;
    }

    /**
     * Builds a half-bounded range condition
     * (for "gt", ">", "gte", ">=", "lt", "<", "lte", "<=" operators)
     * @param string $operator
     * @param array $operands
     * @return array Filter expression
     */
    private function buildHalfBoundedRangeCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidParamException("Operator '$operator' requires two operands.");
        }

        list($column, $value) = $operands;
        if ($column == '_id') {
            $column = '_uid';
        }

        $range_operator = null;

        if (in_array($operator, ['gte', '>='])) {
            $range_operator = 'gte';
        } elseif (in_array($operator, ['lte', '<='])) {
            $range_operator = 'lte';
        } elseif (in_array($operator, ['gt', '>'])) {
            $range_operator = 'gt';
        } elseif (in_array($operator, ['lt', '<'])) {
            $range_operator = 'lt';
        }

        if ($range_operator === null) {
            throw new InvalidParamException("Operator '$operator' is not implemented.");
        }

        $filter = [
            'range' => [
                $column => [
                    $range_operator => $value
                ]
            ]
        ];

        return $filter;
    }

    protected function buildCompositeInCondition($operator, $columns, $values)
    {
        throw new NotSupportedException('composite in is not supported by elasticsearch.');
    }

    private function buildLikeCondition($operator, $operands)
    {
        throw new NotSupportedException('like conditions are not supported by elasticsearch.');
    }

    private function buildShould($operator, $operands){
        if(!is_array($operands))return [];
        if(empty($operands))return [];
        $should = [];
        foreach($operands[0] as $key=>$val){
            if($key == 'in'){
                $should[]['terms'][$val[1]] = $val[2];
            }else{
                $should[]['term'][$key] = $val;
            }

        }
        $this->should = $should;
    }
}
