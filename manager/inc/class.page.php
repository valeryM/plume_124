<?php
/* -*- tab-width: 4; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Plume CMS, a website management application.
# Copyright (C) 2001-2006 Loic d'Anterroches and contributors.
#
# Plume CMS is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Plume CMS is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

class Page extends RecordSet 
{

    /**
     * Get content of a field as text.
     * No modification of the content is performed.
     *
     * @param string Field to get
     * @param bool Escape the & character (true)
     * @return string Content
     */
    function getTextContent($field, $escape=true)
    {
        if ($escape) {
            return str_replace('&', '&amp;', $this->f($field));
        }
        return $this->f($field);
    }
}
?>
