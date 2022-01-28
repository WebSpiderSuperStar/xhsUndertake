local cjson = require 'cjson'
local curl = require 'lcurl'
local iconv = require 'iconv'
-- local sleep = require "sleep"

function curl_get(url, httpheaders, cookie, location)
    local response = {}
    local headers = {}
    local c = curl.easy():setopt_url(url):setopt(curl.OPT_USERAGENT,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36')
        :setopt(curl.OPT_SSL_VERIFYPEER, false):setopt(curl.OPT_SSL_VERIFYHOST, false):setopt(curl.OPT_FOLLOWLOCATION,
            location):setopt(curl.OPT_HEADER, true) -- :setopt(curl.OPT_RETURNTRANSFER, 1)
        :setopt(curl.OPT_HTTPHEADER, httpheaders):setopt(curl.OPT_COOKIE, cookie):setopt(curl.OPT_REFERER, '')
        :setopt_writefunction(function(buf)
            table.insert(response, buf)
            return #buf
        end):setopt_headerfunction(function(buf)
            table.insert(headers, buf)
            return #buf
        end):perform()

    local code = c:getinfo(curl.INFO_RESPONSE_CODE)
    referer = url

    if code == 200 then
        local response = table.concat(response)
        return response
    else
        return "return code not 200 is " .. code
    end
end

function curl_get_post(httpheaders, cookie)
    local response = {}
    local headers = {}
    local c = curl.easy():setopt_url('https://www.xiaohongshu.com/fe_api/burdock/v2/shield/registerCanvas?p=cc'):setopt(
        curl.OPT_HTTPHEADER, httpheaders):setopt(curl.OPT_USERAGENT,
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36')
        :setopt(curl.OPT_SSL_VERIFYPEER, false):setopt(curl.OPT_FOLLOWLOCATION, true):setopt(curl.OPT_HEADER, true)
        :setopt(curl.OPT_SSL_VERIFYPEER, false):setopt(curl.OPT_SSL_VERIFYHOST, false):setopt(curl.OPT_POST, true)
        :setopt(curl.OPT_POSTFIELDS,
            '{"id":"aac519c1dd61ae3cefe8e50f67e2df50","sign":"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.99 Safari/537.36~~~false~~~zh-CN~~~24~~~8~~~8~~~-480~~~Asia/Shanghai~~~1~~~1~~~1~~~1~~~unknown~~~MacIntel~~~PDF Viewer::Portable Document Format::application/pdf~pdf,text/pdf~pdf,Chrome PDF Viewer::Portable Document Format::application/pdf~pdf,text/pdf~pdf,Chromium PDF Viewer::Portable Document Format::application/pdf~pdf,text/pdf~pdf,Microsoft Edge PDF Viewer::Portable Document Format::application/pdf~pdf,text/pdf~pdf,WebKit built-in PDF::Portable Document Format::application/pdf~pdf,text/pdf~pdf~~~canvas winding:yes~canvas fp:79107f0fa43bd9224f152e0f063fc592~~~false~~~false~~~false~~~false~~~false~~~0;false;false~~~2;3;6;7;8~~~124.04344968475198"}')
        :setopt(curl.OPT_COOKIE, cookie):setopt(curl.OPT_REFERER,
            'https://www.xiaohongshu.com/web-login/canvas?redirectPath=http%3A%2F%2Fwww.xiaohongshu.com%2Fdiscovery%2Fitem%2F604ca7db00000000210377ea%3Fxhsshare%3DSinaWeibo%26appuid%3D5e0453640000000001009dd5%26apptime%3D1617267771')
        :setopt_writefunction(function(buf)
            table.insert(response, buf)
            return #buf
        end):setopt_headerfunction(function(buf)
            table.insert(headers, buf)
            return #buf
        end):perform()

    local code = c:getinfo(curl.INFO_RESPONSE_CODE)
    c:close()

    if code == 200 then
        local response = table.concat(response)
        return response
    else
        return "return code not 200 is " .. code
    end
end

local rand2 = {'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
               'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
               'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'}

function get_rand2()
    rand = math.random(62)
    return rand2[rand]
end

function run(params)
    local params = cjson.decode(params)
    local link = params.link
    local cookie = ''
    local httpheaders = {'Connection: keep-alive', 'Pragma: no-cache', 'Cache-Control: no-cache',
                         'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
                         'sec-ch-ua-mobile: ?0', 'Content-Type: application/json', 'Accept: */*',
                         'Origin: https://www.xiaohongshu.com', 'Sec-Fetch-Site: same-origin', 'Sec-Fetch-Mode: cors',
                         'Sec-Fetch-Dest: empty'}
    local prehtml = curl_get(link, httpheaders, cookie, 1)
    if (string.match(prehtml, 'Set%-Cookie:%sxhsTrackerId=(.-);')) then
        xhsTrackerId = string.match(prehtml, "Set%-Cookie:%s(xhsTrackerId=.-;)")
    end
    if (string.match(prehtml, 'Set%-Cookie:%sextra_exp_ids=(.-);')) then
        extra_exp_ids = string.match(prehtml, "Set%-Cookie:%s(extra_exp_ids=.-;)")
    end
    local xhsuid = ''
    for i = 1, 16 do
        xhsuid = xhsuid .. get_rand2()
    end
    -- cookie = xhsTrackerId .. extra_exp_ids .. 'xhsuid=' .. xhsuid .. '; '
    local cookiedata = curl_get_post(httpheaders, cookie)
    if (string.match(cookiedata, 'set%-cookie:%stimestamp2=(.-);')) then
        timestamp2 = string.match(cookiedata, "set%-cookie:%s(timestamp2=.-;)")
    end
    if (string.match(cookiedata, 'set%-cookie:%stimestamp2%.sig=(.-);')) then
        timestamp2sig = string.match(cookiedata, "set%-cookie:%s(timestamp2%.sig=.-;)")
    end
    cookie = xhsTrackerId .. extra_exp_ids .. timestamp2 .. timestamp2sig
    local html = curl_get(link, httpheaders, cookie, 1)
    if (string.match(html, '302%s*Found')) then
        html = '!BLOCK! !BLOCK! ' .. html
    end
    return cjson.encode(html)
end
