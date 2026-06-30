import json

class FormatMessage:
    @staticmethod
    def ident(res):
        return json.dumps({
            "t": "ident",
            "r": res
        })
    
    @staticmethod
    def loc(tk, lat, lng):
        return json.dumps({
            "t": "loc",
            "tk": tk,
            "lat": lat,
            "lng": lng
        })
    
    @staticmethod
    def watchTracker(tk, adding, res):
        return json.dumps({
            "t": "wTrk",
            "tk": tk,
            "a": (adding),
            "r": (res)
        })
