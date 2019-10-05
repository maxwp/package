/**
* WebProduction Packages
* Copyright (C) 2007-2010  WebProduction <webproduction.com.ua>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
* as published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
* Класс-проверщик различных данных на корректность
* JavaScript-версия
*
* @author Max
* @copyright WebProduction
* @package Checker
*/
var Checker = new Object();

Checker.CheckEmail = function() {
    var emailfilter=/^\w+[\+\.\w-]*@([\w-]+\.)*\w+[\w-]*\.([a-z]{2,4}|\d+)$/i
    return emailfilter.test(e.value);
};;