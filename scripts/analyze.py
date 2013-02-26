#!/usr/bin/env python

import os
import urllib
import json
from utils import download
from itertools import combinations
from collections import defaultdict
from utils import write
import argparse

def get_cross(session, chamber, rootdir):
    
    base = "http://www.govtrack.us/data/us/%s/rolls/" % session
    
    chamber = 'senate'
    crossvote = defaultdict(lambda: defaultdict(lambda: [0,0]))
    members = defaultdict(int);
    
    for vote in [x for x in os.listdir(rootdir + "/data/json/%s/%s" % (chamber, session)) if x[-4:] == "json"]:
        data = json.load(open(rootdir + "/data/json/%s/%s/%s" % (chamber, session, vote), 'r'))
        ids = [x for x in data['rollcall'].keys() if data['rollcall'][x] != "Not Voting"]
        for mid in ids:
            members[mid] += 1
            
        for pair in combinations(ids, 2):
            crossvote[pair[0]][pair[1]][1] += 1            
            if data['rollcall'][pair[0]] == data['rollcall'][pair[1]]:
                crossvote[pair[0]][pair[1]][0] += 1
    
    write(json.dumps(crossvote, indent=2), rootdir + "/data/output/%s/%s/crossvote.json" % (chamber, session))
    #write(json.dumps(members.keys(), indent=2), rootdir + "/data/output/%s/%s/members.json" % (chamber, session))


    #write members directory
    pb = {}
    for mid in members.keys():
        member = json.loads(download('http://www.govtrack.us/api/v1/person/' + mid, "members/" + mid + ".json"))
        pb[mid] = {
            'name': member['name'],
            'bioguide': member['bioguideid'],
            'url': member['link'],
            'votes': members[mid]
        }
        
    write(json.dumps(pb, indent=2), rootdir + "/data/output/%s/%s/phonebook.json" % (chamber, session))


def main():
    parser = argparse.ArgumentParser(description="Retrieve rollcall votes for a session of Congress")
    parser.add_argument("-s", "--session", metavar="STRING", dest="session", type=str, default='113',
                        help="a session of congress. Default is 113")
    parser.add_argument("-r", "--rootdir", metavar="STRING", dest="rootdir", type=str, default=os.getcwd(),
                        help="root directory for files. Default is os.getcwd()")
    parser.add_argument("-c", "--chamber", metavar="STRING", dest="chamber", type=str, default="senate",
                        help="chamber ('house' or 'senate'). Default is house")
    args = parser.parse_args()
    get_cross(args.session, args.chamber, args.rootdir)

if __name__ == "__main__":
    main()
    
