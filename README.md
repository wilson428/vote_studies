# Congressional Social Networks

## Quick setup

It's recommended to make a virtualenv, then run:

```bash
pip install -r requirements.txt
```

## Data
All voting information comes from the incomparably awesome GovTrack.us. To prevent unnecessary hammering of GovTrack's API, all cached XML files and the associated JSON files are included in this repository.

If you insist on downloading them yourself, you can do so by running the ```fetch.py``` script:

### fetch.py
Options:

`--session`: Session of Congress for which you want to download votes. Default is 113.

`--rootdir`: Root directory of project. Default is output of ```os.getcwd()```