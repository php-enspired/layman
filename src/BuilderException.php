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

use at\layman\LaymanException;

/**
 * Error cases for Builders.
 *
 * Exception code range is 200-299.
 */
class BuilderException extends LaymanException {

  public const FACTORY_MISMATCH = 201;
  public const UNCLOSED_TOKEN = 202;
  public const TOO_MANY_ARGS = 203;
  public const UNKNOWN_TOKEN = 204;
  public const TOO_FEW_ARGS = 205;
  public const BAD_IDENTIFIER = 206;
  public const BAD_IDENTIFIER_LIST = 207;
  public const BAD_DATA = 208;
  public const BAD_DATA_LIST =209;

  /** @var array[] Error code:details map. */
  protected const INFO = [
    self::FACTORY_MISMATCH => [
      "message" => "this builder supports {builder_type} connections;" .
        " given Factory instance uses the '{type}' driver"
    ],
    self::UNCLOSED_TOKEN => [
      "message" => "template contains an unclosed formatting token"
    ],
    self::TOO_MANY_ARGS => [
      "message" => "too many arguments provided for the given template"
    ],
    self::UNKNOWN_TOKEN => [
      "message" => "unsupported token '{token}' found in template"
    ],
    self::TOO_FEW_ARGS => [
      "message" => "too few arguments provided for the given template"
    ],
    self::BAD_IDENTIFIER => [
      "message" => "identifier '{name}' is invalid or quoting failed"
    ],
    self::BAD_IDENTIFIER_LIST => [
      "message" => "expected list of identifiers; '{type}' provided"
    ],
    self::BAD_DATA => [
      "message" => "unable to parameterize data: {info}"
    ],
    self::BAD_DATA_LIST => [
      "message" => "expected list of data values to parameterize; '{type}' provided"
    ]
  ];
}
