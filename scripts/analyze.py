#!/usr/bin/env python

import os
import urllib
import json
from utils import download
from itertools import combinations
from collections import defaultdict
from utils import write
import argparse

#group lawmakers by seat if didn't finish term
#e.g. John Kerry -> Mo Cowan
replacements = json.load(open(os.getcwd() + "/data/replacements.json", 'r'))

def get_cross(session, chamber, rootdir):
    if session not in replacements:
        replacements[session] = {}
    
    base = "http://www.govtrack.us/data/us/%s/rolls/" % session
    chamber = 'senate'
    all_votes = defaultdict(lambda: [0,[],[]])
    crossvote = defaultdict(lambda: defaultdict(lambda: [0,0]))
    members = defaultdict(lambda: [0,0]);
    
    #load every file in specified directory
    print rootdir + "/data/json/%s/%s" % (chamber, session)
    for vote in [x for x in os.listdir(rootdir + "/data/json/%s/%s" % (chamber, session)) if x[-4:] == "json"]:
        raw_vote = open(rootdir + "/data/json/%s/%s/%s" % (chamber, session, vote), 'r').read()
        
        #fix ids for seats with two members
        for replacement in replacements[session]:
            raw_vote = raw_vote.replace(replacement, replacements[session][replacement]["id"])
        
        data = json.loads(raw_vote)
        #ids = [x for x in data['rollcall'].keys() if data['rollcall'][x] != "Not Voting"]
        ids = [x for x in data['rollcall'].keys() if x != '0']

        #add to vote count 
        for mid in ids:
            if mid == '0':
                print "zero", vote
            if mid in replacements[session]:
                mid = replacements[session][mid]["id"]
            members[mid][1] += 1

        for mid in [x for x in ids if data['rollcall'][x] != "Not Voting"]:
            if mid in replacements[session]:
                mid = replacements[session][mid]["id"]
            members[mid][0] += 1

        for pair in combinations(ids, 2):
            A,B = pair
            
            if int(A) > int(B):
                B,A = A,B
            
            all_votes[A + "_" + B][0] += 1
            crossvote[A][B][1] += 1
            #if voted the same way
            if data['rollcall'][A] == data['rollcall'][B] and data['rollcall'][A] != 'Not Voting':
                crossvote[A][B][0] += 1                           
                all_votes[A + "_" + B][1].append(vote)
            else:
                all_votes[A + "_" + B][2].append(vote)
    
    write(json.dumps(crossvote, indent=2), rootdir + "/data/output/%s/%s/crossvote.json" % (chamber, session))
    #write(json.dumps(all_votes, indent=2), rootdir + "/data/output/%s/%s/all_votes.json" % (chamber, session))


    #write members directory
    pb = {}
    inv_replacements = dict([(v['id'], k) for (k,v) in replacements[session].items()])
    print inv_replacements 
    
    for mid in [x for x in members.keys() if x != '0']:
        raw_member = download('http://www.govtrack.us/api/v1/person/' + mid, "members/" + mid + ".json")
        try:
            member = json.loads(raw_member)
        except:
            print mid, raw_member
        name = member['name']
        if len(inv_replacements.items()) > 0 and mid in inv_replacements:
            alt_member = json.loads(download('http://www.govtrack.us/api/v1/person/' + inv_replacements[mid], "members/" + inv_replacements[mid] + ".json"))
            print alt_member
            name += " / " + alt_member['name']    
        pb[mid] = {
            'name': name,
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
    
