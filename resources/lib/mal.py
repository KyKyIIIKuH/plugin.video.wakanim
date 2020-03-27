# -*- coding: utf-8 -*-
# Wakanim - Watch videos from the german anime platform Wakanim.tv on Kodi.
# Copyright (C) 2017 MrKrabat
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

import os
import sys
from cgi import parse_header
from bs4 import BeautifulSoup
from time import timezone
reload(sys)
sys.setdefaultencoding('utf-8')

PY3 = sys.version_info.major >= 3
if PY3:
    from urllib.parse import urlencode, quote_plus
    from urllib.request import urlopen, build_opener, HTTPCookieProcessor, install_opener
    from http.cookiejar import LWPCookieJar, Cookie
else:
    from urllib import urlencode, quote_plus
    from urllib2 import urlopen, build_opener, HTTPCookieProcessor, install_opener
    from cookielib import LWPCookieJar, Cookie

import xbmc
import xbmcgui

def getTPath(args):
    profile_path = xbmc.translatePath(args._addon.getAddonInfo("profile"))
    if args.PY2:
        return os.path.join(profile_path.decode("utf-8"), u"title_anime.txt")
    else:
        return os.path.join(profile_path, "title_anime.txt")

def auth(args):
    opener = build_opener()
    opener.addheaders = [("User-Agent",      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36"),
                         ("Accept-Encoding", "identity"),
                         ("Accept-Charset",  "utf-8"),
                         ("DNT",             "1")]
    install_opener(opener)

    login = u"%s" % (quote_plus(args._addon.getSetting("mal_username").encode("utf-8")).encode("utf-8"))
    password = u"%s" % (quote_plus(args._addon.getSetting("mal_password").encode("utf-8")).encode("utf-8"))
    response = urlopen("https://ploader.ru/wakanim/myanimelist.php?auth=true&login=%s&password=%s" % (login, password))
    result = response.read().decode(getCharset(response))
    return result

def check_ep(args, id_anime, id_season, id_episode, info):
    opener = build_opener()
    opener.addheaders = [("User-Agent",      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36"),
                         ("Accept-Encoding", "identity"),
                         ("Accept-Charset",  "utf-8"),
                         ("DNT",             "1")]
    install_opener(opener)

    login = u"%s" % (quote_plus(args._addon.getSetting("mal_username").encode("utf-8")).encode("utf-8"))
    response = urlopen("https://ploader.ru/wakanim/myanimelist.php?check_ep=true&login=%s&id_anime=%s&id_season=%s&id_episode=%s" % (login, id_anime, id_season, id_episode))
    result = response.read().decode(getCharset(response))

    if(int(info) == 1):
        dialog = xbmcgui.Dialog()
        dialog.notification(u'MAL', u"Episode %s" % (result), xbmcgui.NOTIFICATION_INFO, 5000)
    return result

def update(args, id_season, ep, id_episode):
    dialog = xbmcgui.Dialog()
    dialog.notification(u'MAL', u"Anime Update Episode Start", xbmcgui.NOTIFICATION_INFO, 5000)
    
    opener = build_opener()
    opener.addheaders = [("User-Agent",      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36"),
                         ("Accept-Encoding", "identity"),
                         ("Accept-Charset",  "utf-8"),
                         ("DNT",             "1")]
    install_opener(opener)

    login = u"%s" % (quote_plus(args._addon.getSetting("mal_username").encode("utf-8")).encode("utf-8"))
    response = urlopen("https://ploader.ru/wakanim/myanimelist.php?update=true&status=1&login=%s&id_season=%s&ep=%s&id_episode=%s" % (login, id_season, ep, id_episode))
    result = response.read().decode(getCharset(response))

    dialog = xbmcgui.Dialog()
    dialog.notification(u'MAL', u"Anime Update Episode Changed", xbmcgui.NOTIFICATION_INFO, 5000)
    return True

def getCharset(response):
    """Get header charset
    """
    _, p = parse_header(response.headers.get("Content-Type", ""))
    return p.get("charset", "utf-8")
