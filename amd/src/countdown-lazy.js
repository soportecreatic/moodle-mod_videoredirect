// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AMD code for video with redirect's interaction functions with
 * activity completion functions.
 *
 * @module     mod_videoredirect/countdown-lazy
 * @class      countdown-lazy
 * @package    core
 * @copyright  2019 Creatic SAS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/ajax', 'media_videojs/video-lazy'],
        function($, notification, Ajax, VideoJS) {
    return {
        init: function() {
            $('.videoredirect.activity').each(function() {
                var $this = $(this), $videoContainer = $this.find('.video-container'),
                $videoJS = $this.find('.video-js'), $videoVimeo = $this.find('.video-vimeo'),
                thisID = $this.attr('id'), thisCM = thisID.replace('module-', ''),
                $notifNextVideo = $this.find('.notif-next-video'),
                cancelled = false, active = 'active', secondsToGo = $videoContainer.data('secondsToGo'),
                redirectURL = $videoContainer.data('url'), timer;

                if($videoJS.length) {
                    var videoID = $videoJS.attr('id');
                } else if ($videoVimeo.length) {
                    var $videoFrame = $this.find('iframe');
                }

                // Function that counts down a second.
                function countDownTimer() {
                    secondsToGo--;
                    $this.find('.countdown').html(secondsToGo);
                    if(secondsToGo > 0) {
                        // If still seconds left, set another timer for same function.
                        timer = setTimeout(countDownTimer, 1000);
                    } else {
                        window.location = redirectURL;
                        $notifNextVideo.removeClass(active);
                        $this.find('h5').removeClass(active);
                        cancelled = true;
                    }
                }

                function setVideoRedirect() {
                    if(!secondsToGo) {
                        return;
                    }

                    if($videoJS.length) {
                        var videoObject = VideoJS.players[videoID];
                        if(!videoObject) {
                            setTimeout(setVideoRedirect, 500);
                            return;
                        }
                    } else if ($videoVimeo.length) {
                        if(typeof Vimeo == 'undefined') {
                            setTimeout(setVideoRedirect, 500);
                            return;
                        } else {
                            var videoObject = new Vimeo.Player($this.find('iframe'));
                        }
                    }

                    // Detect if video has ended to show a semi-transparent notification box.
                    videoObject.on('ended', function() {
                        $notifNextVideo.addClass(active);
                        var setCompletion = Ajax.call([
                            {
                                methodname: 'mod_videoredirect_activity_completion',
                                args: {
                                    cm: thisCM,
                                }
                            }
                        ]);

                        setCompletion[0].done(function(response) {
                        }).fail(notification.exception);

                        if(!cancelled) {
                            timer = setTimeout(countDownTimer, 1000);
                        }
                    });
                }

                setVideoRedirect();

                $this.find('.button.cancel').click(function(e) {
                    e.preventDefault();
                    clearTimeout(timer);
                    $notifNextVideo.removeClass(active);
                    $this.find('h5').removeClass(active);
                    cancelled = true;
                });
                $this.find('.button:not(.cancel)').click(function(e) {
                    $notifNextVideo.removeClass(active);
                    $this.find('h5').removeClass(active);
                    cancelled = true;
                });
            });
        }
    };
});
