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
 * Error cases for Factories.
 *
 * Exception code range is 100-199.
 */
class FactoryException extends LaymanException {

  const NO_SUCH_FACTORY = 101;
  const PDO_MISMATCH = 102;

  const INFO = [
    self::NO_SUCH_FACTORY => [
      "message" => "no factory for '{type}' is registered"
    ],
    self::PDO_MISMATCH => [
      "message" => "this factory supports {factory_type} database connections;" .
        " given PDO instance uses the '{type}' driver"
    ]
  ];
}

