# -*- coding: utf-8-*-
# Экспорт серий с сайта Wakanim в Базу Данных

import re
from bs4 import BeautifulSoup

import urllib3
urllib3.disable_warnings()

import requests
from lxml import html
import sys

import json

def check_CSRF():
	try:
	    # get security tokens
	    soup = BeautifulSoup(result.content, "html.parser")
	    form = soup.find_all("form", {"class": "nav-user_login"})[0]
	    for inputform in form.find_all("input", {"type": "hidden"}):
	        if inputform.get("name") == u"RememberMe":
	            continue
	        payload[inputform.get("name")] = inputform.get("value")
	except Exception as e:
		return False

def check_auth():
	try:
		tree = html.fromstring(result.content)
		Username = tree.xpath("/html/body/header/div[2]/div/div[5]/a/span/span[2]/text()")[0]
		Username = Username.lstrip()
		Username = Username.rstrip()
		print(Username)
	except Exception as e:
		return False

	if(len(Username) <= 5):
		return False
	else:
		return True 

# Get a copy of the default headers that requests would use
headers = requests.utils.default_headers()

_username = "E-MAIL"
_password = "Password"

lang_site = ["ru", "de", "sc", "fr"]

returnurl = None

for lang in lang_site:
	if(lang == "ru"):
		returnurl = "%2Fru%2Fv2"
	if(lang == "de"):
		returnurl = "%2Fde%2Fv2"
	if(lang == "sc"):
		returnurl = "%2Fsc%2Fv2"
	if(lang == "fr"):
		returnurl = "%2Ffr%2Fv2"
	login_url = "https://www.wakanim.tv/%s/v2/account/login?ReturnUrl=%s" % (lang, returnurl)
	url = 'https://www.wakanim.tv/%s/v2' % (lang)

	headers.update(
	    {
	        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36',
	        'Referer': '%s' % (url),
	        'Content-Type': 'application/x-www-form-urlencoded',
	        'DNT': '1',
	    }
	)
	
	payload = {
		"Username": "%s" % (_username), 
		"Password": "%s" % (_password), 
		"login": "",
		"RememberMe": "true",
		"RememberMe": "False",
	}


	if(check_auth() == False):
		print("AUTH proccess")
		session_requests = requests.session()
		result = session_requests.get(url, verify=False, headers=headers, timeout=10)

		_csrf_token = check_CSRF()
		if(_csrf_token == False):
			sys.exit()

		result = session_requests.post(
			login_url, 
			data = payload, 
			headers = headers,
			timeout=10
		)

	if(check_auth() == True):
		print("AUTH")

		result = session_requests.get(
			url,
			verify=False,
			headers = headers,
			timeout=10
		)

		soup = BeautifulSoup(result.content, "html.parser")
		
		if(lang == "ru"):
			slider_wrap = soup.find_all("div", {"class": "slider_wrap"})[3]

		if(lang == "de" or lang == "sc" or lang == "fr"):
			slider_wrap = soup.find_all("div", {"class": "slider_wrap"})[2]

		item = slider_wrap.find_all("li", {"class": "slider_item"})
		status = None
		for row in item:
			if(len(row.find_all("a", {"class": "slider_item_season"}, href=True)) > 0):
				item_season = row.find_all("a", {"class": "slider_item_season"}, href=True)[0]["href"]
				id_season = item_season.split("/")[8]

				item_episode = row.find_all("a", {"class": "slider_item_link"}, href=True)[0]["href"]
				id_episode = item_episode.split("/")[5]

				get_ = requests.get("https://ploader.ru/wakanim/list_anime_wakanim.php?id_season=%s" % (id_season), verify=False, headers=headers, timeout=10)
				parsed_string = json.loads(get_.text)

				if(int(len(parsed_string)) > 0):
					for ep_check in parsed_string:
						if(int(id_episode) == int(ep_check["id_episode"])):
							status = True
							break
				else:
					status = False

				if(status == True):
					print("OK | %s = %s" % (id_season, id_episode))
				else:
					print("ADD | %s = %s" % (id_season, id_episode))

					type_episode = "subtitles"
					item_type = row.find_all("span", {"class": "slider_item_info_text"})[0].text

					if(item_type == "дубляж" or item_type == "озвучка" or item_type == "(DT.)" or item_type == "ENG DUB" or item_type == "VF"): type_episode = "voice"
					if(item_type == "субтитры"): type_episode = "subtitles"

					requests.get("https://ploader.ru/wakanim/insert.php?id_season=%s&id_episode=%s&type_episode=%s" % (id_season, id_episode, type_episode), verify=False, headers=headers, timeout=10)
					#parsed_string = json.loads(get_.text)
					#print(parsed_string)
		#requests.post("https://www.wakanim.tv/%s/v2/account/logoff" % (lang))
		result = None
