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

use at\exceptable\Exception as Exceptable;

/**
 * Base class for all Layman exceptions.
 */
class LaymanException extends Exceptable {

  /**
   * Factory: creates a new LaymanException for the given code.
   *
   * @param int $code Exception code
   * @param array $context Contextual details
   * @return LaymanException
   */
  public static function create(int $code, array $context = []) : LaymanException {
    return new static($code, $context);
  }
}
