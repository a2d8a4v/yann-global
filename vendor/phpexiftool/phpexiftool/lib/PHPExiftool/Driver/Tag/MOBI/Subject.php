<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MOBI;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Subject extends AbstractTag
{

    protected $Id = 105;

    protected $Name = 'Subject';

    protected $FullName = 'Palm::EXTH';

    protected $GroupName = 'MOBI';

    protected $g0 = 'Palm';

    protected $g1 = 'MOBI';

    protected $g2 = 'Document';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Subject';

    protected $flag_List = true;

}
