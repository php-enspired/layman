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
namespace at\layman\Mysql;

use at\layman\ {
  Builder as BaseBuilder,
  BuilderException
};

/**
 * Base class for mysql query builders.
 */
abstract class Builder extends BaseBuilder {

  /** {@inheritDoc} */
  protected const PARSER = [
    self::T_NAME => [self::class, "quoteName"],
    self::T_NAMES => [self::class, "quoteNames"],
    self::T_PARAM => [self::class, "parameterMarker"],
    self::T_PARAMS => [self::class, "parameterMarkers"]
  ];

  public static function quoteName(string $name) : string {}
}
