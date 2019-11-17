<?php
/**
 * @package    at.layman
 * @author     Adrian <adrian@enspi.red>
 * @copyright  2019
 * @license    GPL-3.0 (only)
 *
 *  This program is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License, version 3.
 *  The right to apply the terms of later versions of the GPL is RESERVED.
 *
 *  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *  See the GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along with this program.
 *  If not, see <http://www.gnu.org/licenses/gpl-3.0.txt>.
 */

namespace at\layman;

use PDO,
  PDOStatement;

use at\layman\ {
  Factory,
  LaymanException,
  Mysql\Factory as Mysql,
  Select
};

class Layman {

  protected static $factories = [];

  public static function createFor(PDO $pdo) : Layman {
    return new static(static::findFactoryFor($pdo));
  }

  public function __construct(Factory $factory) {
    $this->factory = $factory;
  }

  public function preparedQuery(string $sql, ...$params) : PDOStatement {
    $statement = $this->pdo->prepare($sql);
    $statement->execute(...$params);
    return $statement;
  }

  public function select(array $select, string $from, string $as = null) : Select {
    return $this->factory->select($from, $as)->setFields($select);
  }

  public function type() : string {
    $factory = $this->factory;
    return $factory::TYPE;
  }

  public function findFactoryFor(PDO $pdo) : Factory {
    $type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // on demand, if we know how
    if (! isset(static::$factories[$type])) {
      switch ($type) {
        case Mysql::TYPE:
          static::setFactoryFor($type, new Mysql($pdo));
        default:
          throw LaymanException::create(
            LaymanException::NO_SUCH_FACTORY,
            ['type' => $type]
          );
      }
    }

    return static::$factories[$type];
  }
}
