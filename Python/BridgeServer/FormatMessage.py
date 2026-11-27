import json

class FormatMessage:
    @staticmethod
    def ident(res, reason=None):
        message = {"t": "ident", "r": res}
        if reason:
            message["reason"] = reason
        return json.dumps(message)

    @staticmethod
    def sessionExpired():
        return json.dumps({"t": "expired"})

    @staticmethod
    def connectedTrackers(request_id, ids):
        return json.dumps({"t": "con", "req": request_id, "ids": ids})
    
    @staticmethod
    def loc(tk, lat, lng, id, date):
        return json.dumps({
            "t": "loc",
            "tk": tk,
            "lat": lat,
            "lng": lng,
            "id": id,
            "date": str(date)
        })
    
    @staticmethod
    def watchTracker(tk, adding, res):
        return json.dumps({
            "t": "wTrk",
            "tk": tk,
            "a": (adding),
            "r": (res)
        })
