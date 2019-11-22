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

/**
 * RDBMS-agnostic façade for using Layman builders and other utilities.
 */
class Layman {

  protected static $factories = ["mysql" => Mysql::class];

  /**
   * Factory: creates a new Layman instance given a PDO connection to use.
   *
   * @param PDO $pdo The Pdo connection to use
   * @return Layman A new Layman instance
   */
  public static function createFromPdo(PDO $pdo) : Layman {
    return new static(static::findFactoryFor($pdo));
  }

  /**
   * Tries to locate / lazily build a factory for the given PDO instance.
   *
   * @param PDO $pdo The Pdo connection to use
   * @throws LaymanException NO_SUCH_FACTORY on failure
   * @return Factory A new factory instance on success
   */
  public static function findFactoryFor(PDO $pdo) : Factory {
    $type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if (isset(static::$factories[$type])) {
      $factory = static::$factories[$type];
    } else {
      // on demand, if we know how
      switch ($type) {
        case Mysql::TYPE:
          $factory = Mysql::class;
        default:
          throw LaymanException::create(
            LaymanException::NO_SUCH_FACTORY,
            ["type" => $type]
          );
      }
    }

    return new $factory($pdo);
  }

  /** @var Factory The Factory instance to use. */
  protected $factory;

  /**
   * @param Factory $factory The Factory to use
   */
  public function __construct(Factory $factory) {
    $this->factory = $factory;
  }

  /**
   * Gets a Delete builder from the factory.
   *
   * @param string $table The table to delete from
   * @param string $as Optional table alias
   * @return Delete A new Delete builder
   */
  public function delete(string $table, string $as = null) : Delete {
    return $this->factory->delete($table, $as);
  }

  /**
   * Gets an Insert builder from the factory and sets the values to insert.
   *
   * @param array $values Column name:value map
   * @param string $table The table to insert to
   * @param string $as Optional table alias
   * @return Delete A new Insert builder
   */
  public function insert(array $values, string $table, string $as = null) : Insert {
    return $this->factory->insert($table, $as)->setValues($values);
  }

  /**
   * Prepares a statement.
   *
   * @param string $sql The query to prepare
   * @throws PDOException On failure
   * @return PDOStatement The prepared statement
   */
  public function prepare(string $sql) : PDOStatement {
    return $this->factory->prepare($sql);
  }

  /**
   * Prepares and executes a parameterized statement.
   *
   * @param string $sql The query to execute
   * @param array $params Parameter position|name:value map
   * @throws PDOException On failure
   * @return PDOStatement The prepared and executed statement
   */
  public function query(string $sql, array $params = []) : PDOStatement {
    $statement = $this->prepare($sql);
    $statement->execute($params);
    return $statement;
  }

  /**
   * Gets a Select builder from the factory and sets the fields to select.
   *
   * @param array $select Field list, with optional
   * @param string $table The table to select from
   * @param string $as Optional table alias
   * @return Delete A new Select builder
   */
  public function select(array $select, string $table, string $as = null) : Select {
    return $this->factory->select($table, $as)->setFields($select);
  }

  /**
   * Gets this layman's factory type (the RDBMS it uses).
   *
   * @return string The factory type
   */
  public function type() : string {
    $factory = $this->factory;
    return $factory::TYPE;
  }

  /**
   * Gets an Update builder from the factory and sets the values to update.
   *
   * @param array $values Column name:value map
   * @param string $table The table to update
   * @param string $as Optional table alias
   * @return Delete A new Update builder
   */
  public function update(array $values, string $table, string $as = null) : Update {
    return $this->factory->update($table, $as)->setValues($values);
  }
}
