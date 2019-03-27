<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2019  Kirill Yegorov
 *  
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Dvelum\Designer\Project\Methods;

class Item
{
    protected $name;
    protected $code = '';
    protected $description = '';
    protected $params = array();

    /**
     * Constructor
     * @param string $name
     * @param array $params , optional array(array('type'=>'' , name=>''))
     */
    public function __construct($name, $params = false)
    {
        $this->setName($name);
        if (is_array($params) && !empty($params))
            $this->addParams($params);
    }

    /**
     * Set method name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get method Name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get method params
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Remove params
     */
    public function removeParams()
    {
        $this->params = [];
    }

    /**
     * Set method params
     * @param array $params array(array('type'=>'' , name=>''))
     */
    public function setParams(array $params)
    {
        $this->removeParams();
        $this->addParams($params);
    }

    /**
     * Add parametr
     * @param string $name
     * @param string $type , optional
     */
    public function addParam($name, $type = '')
    {
        $this->params[] = ['name' => $name, 'type' => $type];
    }

    /**
     * Add method parametrs
     * @param array $data array(array('type'=>'' , name=>''))
     */
    public function addParams(array $data)
    {
        if (empty($data))
            return;

        foreach ($data as $v) {
            $this->addParam($v['name'], $v['type']);
        }
    }

    /**
     * Remove method param
     * @param integer $index
     */
    public function removeParam($index)
    {
        if (empty($this->params))
            return;

        $new = array();
        foreach ($this->params as $paramIndex => $data) {
            if ($paramIndex == $index)
                continue;
            $new[] = $data;
        }
        $this->params = $new;
    }

    /**
     * Set method code
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get method code
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get params list as string for description
     * @return string
     */
    public function getParamsAsDescription()
    {
        if (empty($this->params))
            return '';

        $params = array();

        if (!empty($this->params))
            foreach ($this->params as $item)
                $params[] = $item['type'] . ' ' . $item['name'];

        return implode(' , ', $params);
    }

    /**
     * Set method description
     * @param string $text
     */
    public function setDescription($text)
    {
        $this->description = $text;
    }

    /**
     * Get Method description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get method JsDoc string
     */
    public function getJsDoc()
    {
        $description = "";
        $descLines = explode("\n", $this->description);

        if (!empty($descLines))
            $description = "* " . implode("\n * ", $descLines);

        $s = "/**\n " . $description . "\n *\n";

        if (!empty($this->params))
            foreach ($this->params as $param)
                $s .= " * @param " . $param['type'] . " " . $param['name'] . "\n";

        $s .= " */";
        return $s;
    }

    /**
     * Conver method data into array
     * @return array
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'params' => $this->params,
            'paramsLine' => $this->getParamsAsDescription(),
            'jsdoc' => $this->getJsDoc()
        );
    }

    /**
     * Get params line (for js code)
     * @return string
     */
    public function getParamsLine()
    {
        if (empty($this->params))
            return '';

        $params = array();

        if (!empty($this->params))
            foreach ($this->params as $item)
                $params[] = $item['name'];

        return implode(' , ', $params);
    }
}