<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\BotMattermostGit\SenderServices;

require_once dirname(__FILE__).'/../../bootstrap.php';

use TuleapTestCase;

class EncoderMessageTest extends TuleapTestCase
{

    private $encoder_message;
    private $bot;

    public function setUp()
    {
        parent::setUp();
        $this->bot = mock('Tuleap\\BotMattermost\\Bot\\Bot');
        $this->encoder_message = new EncoderMessage();
    }

    public function itVerifiedThatFormatingMessageReturnsPostFormatForMattermost()
    {
        $text = "text";
        stub($this->bot)->getName()->returns("toto");
        $result = $this->encoder_message->generateMessage($this->bot, $text);
        $this->assertEqual($result, '{"username":"toto","text":"text"}');
    }
}
