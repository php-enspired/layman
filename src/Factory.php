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

use PDO;

use at\layman\ {
  Delete,
  Insert,
  LaymanException,
  Select,
  Update
};

/**
 * Base class for Builder Factories.
 */
abstract class Factory {

  /** @var string PDO driver type this builder supports. */
  public const TYPE = '';

  /** @var PDO This factory's PDO connection. */
  protected $pdo;

  /**
   * Gets a Delete builder.
   *
   * @param string $table The table to delete from
   * @param string $as Optional table alias
   * @return Delete A new Delete builder
   */
  abstract public function delete(string $table, string $as = null) : Delete;

  /**
   * Gets an Insert builder.
   *
   * @param string $table The table to insert to
   * @param string $as Optional table alias
   * @return Delete A new Insert builder
   */
  abstract public function insert(string $table, string $as = null) : Insert;

  /**
   * Gets a Select builder.
   *
   * @param string $table The table to select from
   * @param string $as Optional table alias
   * @return Delete A new Select builder
   */
  abstract public function select(string $table, string $as = null) : Select;

  /**
   * Gets an Update builder.
   *
   * @param string $table The table to update
   * @param string $as Optional table alias
   * @return Delete A new Update builder
   */
  abstract public function update(string $table, string $as = null) : Update;

  /**
   * @param PDO $pdo The Pdo connection to use
   * @throws LaymanException PDO_MISMATCH if pdo instance uses the wrong driver for this factory
   */
  public function __construct(PDO $pdo) {
    $type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($type !== static::TYPE) {
      throw FactoryException::create(
        FactoryException::PDO_MISMATCH,
        ["factory_type" => static::TYPE, "type" => $type]
      );
    }

    $this->pdo = $pdo;
  }

  /**
   * Prepares a statement.
   *
   * @param string $sql The query to prepare
   * @throws PDOException On failure
   * @return PDOStatement The prepared statement
   */
  public function prepare(string $sql) : PDOStatement {
    return $this->pdo->prepare($sql);
  }
}
