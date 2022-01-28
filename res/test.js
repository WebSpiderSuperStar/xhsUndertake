var r, i, a, o, s;
var n = {
    utf8: {
        stringToBytes: function(e) {
            return n.bin.stringToBytes(unescape(encodeURIComponent(e)))
        },
        bytesToString: function(e) {
            return decodeURIComponent(escape(n.bin.bytesToString(e)))
        }
    },
    bin: {
        stringToBytes: function(e) {
            for (var t = [], n = 0; n < e.length; n++)
                t.push(255 & e.charCodeAt(n));
            return t
        },
        bytesToString: function(e) {
            for (var t = [], n = 0; n < e.length; n++)
                t.push(String.fromCharCode(e[n]));
            return t.join("")
        }
    }
};
r = {
    rotl: function(e, t) {
        return e << t | e >>> 32 - t
    },
    rotr: function(e, t) {
        return e << 32 - t | e >>> t
    },
    endian: function(e) {
        if (e.constructor == Number)
            return 16711935 & r.rotl(e, 8) | 4278255360 & r.rotl(e, 24);
        for (var t = 0; t < e.length; t++)
            e[t] = r.endian(e[t]);
        return e
    },
    randomBytes: function(e) {
        for (var t = []; e > 0; e--)
            t.push(Math.floor(256 * Math.random()));
        return t
    },
    bytesToWords: function(e) {
        for (var t = [], n = 0, r = 0; n < e.length; n++,
            r += 8)
            t[r >>> 5] |= e[n] << 24 - r % 32;
        return t
    },
    wordsToBytes: function(e) {
        for (var t = [], n = 0; n < 32 * e.length; n += 8)
            t.push(e[n >>> 5] >>> 24 - n % 32 & 255);
        return t
    },
    bytesToHex: function(e) {
        for (var t = [], n = 0; n < e.length; n++)
            t.push((e[n] >>> 4).toString(16)),
                t.push((15 & e[n]).toString(16));
        return t.join("")
    },
    hexToBytes: function(e) {
        for (var t = [], n = 0; n < e.length; n += 2)
            t.push(parseInt(e.substr(n, 2), 16));
        return t
    },
    bytesToBase64: function(e) {
        for (var t = [], r = 0; r < e.length; r += 3)
            for (var i = e[r] << 16 | e[r + 1] << 8 | e[r + 2], a = 0; a < 4; a++)
                8 * r + 6 * a <= 8 * e.length ? t.push(n.charAt(i >>> 6 * (3 - a) & 63)) : t.push("=");
        return t.join("")
    }
},
    i = {
        stringToBytes: function(e) {
            return n.bin.stringToBytes(unescape(encodeURIComponent(e)))
        },
        bytesToString: function(e) {
            return decodeURIComponent(escape(n.bin.bytesToString(e)))
        }
    },
    a = function(e){
        return false
    },
    o = {
        stringToBytes: function(e) {
            for (var t = [], n = 0; n < e.length; n++)
                t.push(255 & e.charCodeAt(n));
            return t
        },
        bytesToString: function(e) {
            for (var t = [], n = 0; n < e.length; n++)
                t.push(String.fromCharCode(e[n]));
            return t.join("")
        }
    },
    (s = function(e, t) {
            e.constructor == String ? e = t && "binary" === t.encoding ? o.stringToBytes(e) : i.stringToBytes(e) : a(e) ? e = Array.prototype.slice.call(e, 0) : Array.isArray(e) || e.constructor === Uint8Array || (e = e.toString());
            for (var n = r.bytesToWords(e), c = 8 * e.length, u = 1732584193, l = -271733879, f = -1732584194, d = 271733878, h = 0; h < n.length; h++)
                n[h] = 16711935 & (n[h] << 8 | n[h] >>> 24) | 4278255360 & (n[h] << 24 | n[h] >>> 8);
            n[c >>> 5] |= 128 << c % 32,
                n[14 + (c + 64 >>> 9 << 4)] = c;
            var p = s._ff
                , v = s._gg
                , m = s._hh
                , g = s._ii;
            for (h = 0; h < n.length; h += 16) {
                var y = u
                    , b = l
                    , w = f
                    , _ = d;
                u = p(u, l, f, d, n[h + 0], 7, -680876936),
                    d = p(d, u, l, f, n[h + 1], 12, -389564586),
                    f = p(f, d, u, l, n[h + 2], 17, 606105819),
                    l = p(l, f, d, u, n[h + 3], 22, -1044525330),
                    u = p(u, l, f, d, n[h + 4], 7, -176418897),
                    d = p(d, u, l, f, n[h + 5], 12, 1200080426),
                    f = p(f, d, u, l, n[h + 6], 17, -1473231341),
                    l = p(l, f, d, u, n[h + 7], 22, -45705983),
                    u = p(u, l, f, d, n[h + 8], 7, 1770035416),
                    d = p(d, u, l, f, n[h + 9], 12, -1958414417),
                    f = p(f, d, u, l, n[h + 10], 17, -42063),
                    l = p(l, f, d, u, n[h + 11], 22, -1990404162),
                    u = p(u, l, f, d, n[h + 12], 7, 1804603682),
                    d = p(d, u, l, f, n[h + 13], 12, -40341101),
                    f = p(f, d, u, l, n[h + 14], 17, -1502002290),
                    u = v(u, l = p(l, f, d, u, n[h + 15], 22, 1236535329), f, d, n[h + 1], 5, -165796510),
                    d = v(d, u, l, f, n[h + 6], 9, -1069501632),
                    f = v(f, d, u, l, n[h + 11], 14, 643717713),
                    l = v(l, f, d, u, n[h + 0], 20, -373897302),
                    u = v(u, l, f, d, n[h + 5], 5, -701558691),
                    d = v(d, u, l, f, n[h + 10], 9, 38016083),
                    f = v(f, d, u, l, n[h + 15], 14, -660478335),
                    l = v(l, f, d, u, n[h + 4], 20, -405537848),
                    u = v(u, l, f, d, n[h + 9], 5, 568446438),
                    d = v(d, u, l, f, n[h + 14], 9, -1019803690),
                    f = v(f, d, u, l, n[h + 3], 14, -187363961),
                    l = v(l, f, d, u, n[h + 8], 20, 1163531501),
                    u = v(u, l, f, d, n[h + 13], 5, -1444681467),
                    d = v(d, u, l, f, n[h + 2], 9, -51403784),
                    f = v(f, d, u, l, n[h + 7], 14, 1735328473),
                    u = m(u, l = v(l, f, d, u, n[h + 12], 20, -1926607734), f, d, n[h + 5], 4, -378558),
                    d = m(d, u, l, f, n[h + 8], 11, -2022574463),
                    f = m(f, d, u, l, n[h + 11], 16, 1839030562),
                    l = m(l, f, d, u, n[h + 14], 23, -35309556),
                    u = m(u, l, f, d, n[h + 1], 4, -1530992060),
                    d = m(d, u, l, f, n[h + 4], 11, 1272893353),
                    f = m(f, d, u, l, n[h + 7], 16, -155497632),
                    l = m(l, f, d, u, n[h + 10], 23, -1094730640),
                    u = m(u, l, f, d, n[h + 13], 4, 681279174),
                    d = m(d, u, l, f, n[h + 0], 11, -358537222),
                    f = m(f, d, u, l, n[h + 3], 16, -722521979),
                    l = m(l, f, d, u, n[h + 6], 23, 76029189),
                    u = m(u, l, f, d, n[h + 9], 4, -640364487),
                    d = m(d, u, l, f, n[h + 12], 11, -421815835),
                    f = m(f, d, u, l, n[h + 15], 16, 530742520),
                    u = g(u, l = m(l, f, d, u, n[h + 2], 23, -995338651), f, d, n[h + 0], 6, -198630844),
                    d = g(d, u, l, f, n[h + 7], 10, 1126891415),
                    f = g(f, d, u, l, n[h + 14], 15, -1416354905),
                    l = g(l, f, d, u, n[h + 5], 21, -57434055),
                    u = g(u, l, f, d, n[h + 12], 6, 1700485571),
                    d = g(d, u, l, f, n[h + 3], 10, -1894986606),
                    f = g(f, d, u, l, n[h + 10], 15, -1051523),
                    l = g(l, f, d, u, n[h + 1], 21, -2054922799),
                    u = g(u, l, f, d, n[h + 8], 6, 1873313359),
                    d = g(d, u, l, f, n[h + 15], 10, -30611744),
                    f = g(f, d, u, l, n[h + 6], 15, -1560198380),
                    l = g(l, f, d, u, n[h + 13], 21, 1309151649),
                    u = g(u, l, f, d, n[h + 4], 6, -145523070),
                    d = g(d, u, l, f, n[h + 11], 10, -1120210379),
                    f = g(f, d, u, l, n[h + 2], 15, 718787259),
                    l = g(l, f, d, u, n[h + 9], 21, -343485551),
                    u = u + y >>> 0,
                    l = l + b >>> 0,
                    f = f + w >>> 0,
                    d = d + _ >>> 0
            }
            return r.endian([u, l, f, d])
        }
    )._ff = function(e, t, n, r, i, a, o) {
        var s = e + (t & n | ~t & r) + (i >>> 0) + o;
        return (s << a | s >>> 32 - a) + t
    }
    ,
    s._gg = function(e, t, n, r, i, a, o) {
        var s = e + (t & r | n & ~r) + (i >>> 0) + o;
        return (s << a | s >>> 32 - a) + t
    }
    ,
    s._hh = function(e, t, n, r, i, a, o) {
        var s = e + (t ^ n ^ r) + (i >>> 0) + o;
        return (s << a | s >>> 32 - a) + t
    }
    ,
    s._ii = function(e, t, n, r, i, a, o) {
        var s = e + (n ^ (t | ~r)) + (i >>> 0) + o;
        return (s << a | s >>> 32 - a) + t
    }
    ,
    s._blocksize = 16,
    s._digestsize = 16;

var array_data = process.argv.splice(2);
var noteid = array_data[0]
var endId = array_data[1]
if (typeof(endId)=="undefined"){
    var url = '/fe_api/burdock/qq/v2/notes/' + noteid + '/comments?pageSize=10WSUDD';
} else {
    var url = '/fe_api/burdock/qq/v2/notes/' + noteid + '/comments?pageSize=10&endId=' + endId + 'WSUDD';
}
var t = undefined
var x_sign = a(url, t, r, s, t, o)
var k = r.wordsToBytes(s(url, t))
var x_sign = 'X' + r.bytesToHex(k)
console.log(x_sign)
