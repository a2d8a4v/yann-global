<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Torrent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Pieces extends AbstractTag
{

    protected $Id = 'pieces';

    protected $Name = 'Pieces';

    protected $FullName = 'Torrent::Info';

    protected $GroupName = 'Torrent';

    protected $g0 = 'Torrent';

    protected $g1 = 'Torrent';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Pieces';

}
