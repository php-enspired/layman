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
  PDOException,
  PDOStatement;

use at\layman\ {
  BuilderException,
  LaymanException
};

/**
 * Base class for query builders.
 */
abstract class Builder {

  /** @var string Key for parsed SQL (fragment). */
  public const SQL = 'sql';

  /** @var string PDO driver type this builder supports. */
  public const TYPE = '';

  /**
   * Templating token components.
   *
   * @var string T_OPEN
   * @var string T_CLOSE
   * @var string T_NAME
   * @var string T_NAMES
   * @var string T_PARAM
   * @var string T_PARAMS
   */
  protected const T_OPEN = '{';
  protected const T_CLOSE = '}';
  protected const T_NAME = '_';
  protected const T_PARAM = '?';
  protected const T_NAMES = '_+';
  protected const T_PARAMS = '?+';

  /**
   * Template token:parser map.
   *
   * Parsers must have the following signature:
   *  parser(array $parsed, scalar|scalar[] $argument) : array $parsed
   *
   * @var callable[]
   */
  protected const PARSER = [];

  /**
   * Prepares a Statement object from the query and binds initial parameters.
   *
   * @throws PDOException If preparing the statement fails
   * @return PDOStatement The prepared and bound statement
   */
  abstract public function prepare() : PDOStatement;

  /** @var PDO This builder's PDO connection. */
  protected $pdo;

  /**
   * @param PDO $pdo The Pdo connection to use
   * @throws LaymanException PDO_MISMATCH if pdo instance uses the wrong driver for this builder
   */
  public function __construct(PDO $pdo) {
    $type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($type !== static::TYPE) {
      throw LaymanException::create(LaymanException::PDO_MISMATCH, ['type' => $type]);
    }

    $this->pdo = $pdo;
  }

  /**
   * Prepares a Statement object from the query and executes it.
   *
   * @throws PDOException If preparing or executing the statement fails
   * @return PDOStatement The prepared, bound, and executed statement
   */
  public function execute() : PDOStatement {
    $statement = $this->prepare();
    $statement->execute();
    return $statement;
  }

  /**
   * Parses and performs replacements on an SQL template.
   *
   * This method is intended for internal use, but is publicly available if needed.
   * Note it does not parse, validate, or make corrections to SQL.
   *
   * This method produces SQL with positional (?) parameter markers.
   * Templating strings should not contain any parameter markers at all,
   * and the returned SQL (fragment)
   * should not be combined with SQL that uses named parameter markers.
   *
   * Applications should define ALL SQL (fragments) as constants or literals.
   * ALL variable instructions should be templated.
   * ALL variable data passed in a query should be parameterized.
   *
   * The number of {} tokens in the template must match the arg count.
   *
   * Recognizes the following tokens:
   *  Instructions:
   *  - {_} Identifier (table name, field name, etc.)
   *  - {_+} List of identifiers
   *  Data:
   *  - {?} Value (string, integer, boolean, decimal, null, etc.)
   *  - {?+} List of values
   *
   * Note that {_+} and {?+} only make sense where lists of values are permissible in SQL;
   * e.g., in a field list or an IN() statement.
   *
   * @param string $template SQL (fragment) with formatting tokens
   * @param scalar|scalar[] ...$args Template replacements/parameter values
   * @return array
   *  - string $sql Paramaterized SQL (fragment)
   *  - scalar $... Ordered list of parameter values
   */
  public function parse(string $template, ...$args) : array {
    // kick off
    $token = self::T_OPEN;
    $parsed = [self::SQL => strtok($template, $token)];
    while (true) {
      // toggle between opening and closing {braces}; find next segment
      $token = ($token === self::T_OPEN) ? self::T_CLOSE : self::T_OPEN;
      $segment = strtok($token);

      // no more segments
      if ($segment === false) {
        // ...but we have an open {
        if ($token === self::CLOSE) {
          throw LaymanException::create(LaymanException::UNCLOSED_TOKEN);
        }
        // ...but we still have args
        if (! empty($args)) {
          throw LaymanException::create(LaymanException::TOO_MANY_ARGS);
        }

        // we're done!
        return $parsed;
      }

      // this segment was literal sql
      if ($token === self::T_OPEN) {
        $parsed[self::SQL] .= $segment;
        continue;
      }

      // this segment was a templating token
      if (! isset(static::PARSER[$segment])) {
        throw LaymanException::create(
          LaymanException::INVALID_TOKEN,
          ['token' => "{{$segment}}"]
        );
      }
      if (empty($args)) {
        throw LaymanException::create(LaymanException::TOO_FEW_ARGS);
      }
      $parsed = static::PARSER[$segment]($parsed, array_shift($args));




      //$arg = array_shift($args);
      //switch ($segment) {
      //  case self::T_PARAM:
      //    $arg = [$arg];
      //    // fall through
      //  case self::T_PARAMS:
      //    $parsed[self::SQL] .= $this->markers($arg);
      //    array_push($parsed, ...$arg);
      //    break;
      //  case self::T_NAME:
      //    $arg = [$arg];
      //    // fall through
      //  case self::T_NAMES:
      //    $parsed[self::SQL] .= $this->names($arg);
      //    break;
      //  default:
      //    throw LaymanException::create(
      //      LaymanException::INVALID_TOKEN,
      //      ['token' => "{{$segment}}"]
      //    );
      //}
    }
  }
}
