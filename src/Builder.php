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

use at\layman\BuilderException;

/**
 * Base class for query builders.
 */
abstract class Builder {

  /** @var int Key for the SQL (fragment) returned from build()/parse(). */
  public const SQL = 0;

  /** @var int Key for the data values returned from build(). */
  public const DATA = 1;

  /** @var string PDO driver type this builder supports. */
  public const TYPE = "";

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
  protected const T_OPEN = "{";
  protected const T_CLOSE = "}";
  protected const T_NAME = "_";
  protected const T_PARAM = "?";
  protected const T_NAMES = "_+";
  protected const T_PARAMS = "?+";

  /** @var Factory This builder's Factory. */
  protected $factory;

  /**
   * Template token:formatter callback map.
   *
   * Formatter callbacks must have the following signature:
   *  formatter(array $parsed, scalar|scalar[] $argument) : array $parsed
   *
   * Formatter callbacks must throw a BuilderException on failure.
   *
   * Every builder must support the following tokens at a minimum:
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
   * @var callable[]
   */
  protected $formatter = [];

  /**
   * @param Factory $factory The Factory to use
   * @throws BuilderException FACTORY_MISMATCH if type doesn't match builder type
   */
  public function __construct(Factory $factory) {
    if ($factory::TYPE !== static::TYPE) {
      throw BuilderException::create(
        BuilderException::FACTORY_MISMATCH,
        ["builder_type" => static::TYPE, "type" => $factory::TYPE]
      );
    }
    $this->factory = $factory;
    $this->setupFormatters();
  }

  /**
   * Performs replacements on an SQL template.
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
          throw BuilderException::create(BuilderException::UNCLOSED_TOKEN);
        }
        // ...but we still have args
        if (! empty($args)) {
          throw BuilderException::create(BuilderException::TOO_MANY_ARGS);
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
      if (! isset($this->parser[$segment])) {
        // ...but we don't have a parser for this token
        throw BuilderException::create(
          BuilderException::UNKNOWN_TOKEN,
          ["token" => "{{$segment}}"]
        );
      }
      // ...but we have no more args
      if (empty($args)) {
        throw BuilderException::create(BuilderException::TOO_FEW_ARGS);
      }

      // grab parser and run it
      $parsed = $this->parser[$segment]($parsed, array_shift($args));
    }
  }

  /**
   * Prepares a Statement object from the query and binds initial parameters.
   *
   * @throws PDOException If preparing the statement fails
   * @return PDOStatement The prepared and bound statement
   */
  public function prepareStatement() : PDOStatement {
    [self::SQL => $sql, self::DATA => $data] = $this->build();

    $statement = $this->factory->prepare($sql);
    $this->bindParameters($statement, $data);
    return $statement;
  }

  /**
   * Sets up parser callbacks for supported templating tokens.
   */
  protected function setupFormatters() : void {
    $this->formatter[self::T_NAME] = [$this, 'formatName'];
    $this->formatter[self::T_NAMES] = [$this, 'formatNameList'];
    $this->formatter[self::T_PARAM] = [$this, 'formatParameter'];
    $this->formatter[self::T_PARAMS] = [$this, 'formatParameterList'];
  }

  /**
   * Binds data to the given statement as parameters.
   *
   * @param PDOStatement The Statement instance to bind parameters to
   * @param scalar[] $data The values to bind
   * @throws PDOException If preparing the statement fails
   * @return PDOStatement The prepared and bound statement
   */
  abstract protected function bindParameters(PDOStatement $statement, array $data) : void;

  /**
   * Builds the SQL statement and parameter list from this builder's current state.
   *
   * @return array
   *  - string ${self::SQL} SQL query
   *  - scalar[] ${self::DATA} Data to be bound to parameters
   */
  abstract protected function build() : array;

  /**
   * Quotes an identifier and adds it to the parsed sql.
   *
   * @param array $parsed Parsed statement and parameters
   * @param string|mixed The identifier to quote
   * @throws BuilderException BAD_IDENTIFIER on failure
   * @return array Parsed statement and parameters
   */
  abstract protected function formatName(array $parsed, $arg) : array;

  /**
   * Quotes a list of identifiers and adds them to the parsed sql.
   *
   * @param array $parsed Parsed statement and parameters
   * @param string[]|mixed The identifiers to quote
   * @throws BuilderException BAD_IDENTIFIER_LIST on failure
   * @return array Parsed statement and parameters
   */
  abstract protected function formatNameList(array $parsed, $arg) : array;

  /**
   * Adds a parameter marker to the parsed sql and appends valid data to the parameter list.
   *
   * @param array $parsed Parsed statement and parameters
   * @param scalar|mixed The data to parameterize
   * @throws BuilderException BAD_DATA on failure
   * @return array Parsed statement and parameters
   */
  abstract protected function formatParameter(array $parsed, $arg) : array;

  /**
   * Adds parameter markers to the parsed sql and appends valid data to the parameter list.
   *
   * @param array $parsed Parsed statement and parameters
   * @param scalar[]|mixed The data to parameterize
   * @throws BuilderException BAD_DATA_LIST on failure
   * @return array Parsed statement and parameters
   */
  abstract protected function formatParameterList(array $parsed, $args) : array;
}
