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

namespace Dvelum\Designer\Storage\Adapter;

use Dvelum\Config\ConfigInterface;
use Dvelum\Designer\Project;
use \Exception;

/**
 * Abstract Base for Designer_Storage
 * @author Kirill A Egorov 2012
 */
abstract class AbstractAdapter
{
    /**
     * Adapter config
     * @var ConfigInterface - optional
     */
    protected $config;

    /**
     * Adapter errors
     */
    protected $errors = [];

    /**
     * @param ConfigInterface $config , optional
     */
    public function __construct(?ConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * Load Designer_Project object
     * @param string $id
     * @return Project
     */
    abstract public function load($id): Project;

    /**
     * Import  Designer_Project  from contents
     * @param string $id
     * @return Project|null
     */
    abstract public function import($id): ?Project;

    /**
     * Save Db_Query object
     * @param string $id
     * @param Project $obj
     * @param bool $export , default false
     * @return bool
     */
    abstract public function save($id, Project $obj, bool $export = false): bool;

    /**
     * Delete Designer_Project object
     * @param string $id
     * @return bool
     */
    abstract public function delete($id) : bool ;

    /**
     * Pack object
     * @param Project $query
     * @return string
     */
    protected function pack(Project $query): string
    {
        return base64_encode(serialize($query));
    }

    /**
     * Unpack object
     * @param string $data
     * @throws Exception
     * @return Project
     */
    protected function unpack($data) : Project
    {
        $query = unserialize(base64_decode($data));

        if (!$query instanceof Project) {
            throw new Exception('Invalid data type');
        }

        return $query;
    }

    /**
     * Get errors
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}