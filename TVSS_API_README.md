# TV Schedules Service (TVSS) API

## Overview

TV Schedules Service (TVSS) is a read-only API that exposes listing metadata in a simple, easy-to-use format, and allows you to create applications that use TV Schedules metadata. As of October 9, 2018, Gracenote is the data source PBS uses for TV Schedules programs, listings, and headends, replacing longtime provider, TiVo/Rovi.

Listing results typically contain two weeks of schedule information.

## Technical Details

- **General**: Django, Python 3, PostgreSQL
- **API**: JSON, SSL + Basic Auth, Read

## Available Content

Types of content available in the read-only API:

- Listing data (today and by date)
- Program information
- Episode information
- Station feeds
- Station headends/providers
- Listing search
- PBS KIDS programming
- PBS-hosted Gracenote images for select endpoints

## Access

If you are a PBS station or producer, and are not already using the TVSS API, request a read-only API key from the Support team. The TVSS API is not available to the general public nor is it available to non-PBS entities.

## Authentication & Request Header

Some of the endpoints are protected by a private key, and are noted below.

For endpoints requiring authentication: In order to do a successful request a client must include in the HTTP request a special header named `X-PBSAUTH` with your secret key.

## General Error Handling & Special Cases

### Query Parameters

| Placeholder | Guidelines | Examples |
|------------|------------|----------|
| `<callsign>` | The callsign is case-insensitive (except when returning JSONP use all lowercase) however, the API endpoint will redirect all requests that don't have a lowercase callsign to an equivalent URL that does. Querying using a nonexistent callsign will return a 404 Not found. | weta, KAKM |
| `<date>` | Format: YYYYMMDD. Using an invalid date will return a 404 Not found. | 20181009 |
| `<zip_code>` | ZIP code must have five characters (if smaller, left pad with 0s). Using an invalid ZIP code will return a 404 Not found. | 22202, 00123 |

### Optional Parameters

| Parameter name | Value | Description |
|---------------|-------|-------------|
| fetch-images | N/A | For the applicable endpoints (below), the `fetch-images` parameter will return Gracenote images for the object, when available. |

## Production API Endpoints

⚠️ **Important**: Make sure you are using **https**
- Using the http protocol will redirect to https but will initially return a 301 status code, prior to the 200 you'll receive from the redirected URL

### Base Endpoint
```
https://tvss.services.pbs.org/tvss/
```

### Available Endpoints

#### Listings Endpoints

1. **Listings (by callsign, date)**
   ```
   https://tvss.services.pbs.org/tvss/<callsign>/day/<yyyymmdd>/?fetch-images
   ```
   *Requires authentication*

2. **KIDS listings (by callsign, date)**
   ```
   https://tvss.services.pbs.org/tvss/<callsign>/day/<yyyymmdd>/kids/?fetch-images
   ```
   *Requires authentication*

3. **Listings for today (by callsign)**
   ```
   https://tvss.services.pbs.org/tvss/<callsign>/today/?fetch-images
   ```
   *Requires authentication*

4. **KIDS listings for today (by callsign)**
   ```
   https://tvss.services.pbs.org/tvss/<callsign>/today/kids/?fetch-images
   ```
   *Requires authentication*

5. **Listings for a single feed CID (by callsign, date)**
   ```
   https://tvss.services.pbs.org/tvss/<callsign>/day/<yyyymmdd>/<feed-cid>/?fetch-images
   ```
   *Requires authentication*

6. **Listings for a single feed CID (by date; no callsign)**
   ```
   https://tvss.services.pbs.org/tvss/feed/<feed-cid>/day/<yyyymmdd>/?fetch-images
   ```
   *Requires authentication*

7. **Listings for today for a single feed CID (by callsign)**
   ```
   https://tvss.services.pbs.org/tvss/<callsign>/today/<feed-cid>/?fetch-images
   ```
   *Requires authentication*

8. **Listings for today for a single feed CID (no callsign)**
   ```
   https://tvss.services.pbs.org/tvss/feed/<feed-cid>/today/?fetch-images
   ```
   *Requires authentication*

> **Note**: All `/upcoming/` endpoints do not require the fetch-images parameter, and will return Gracenote images by default, when they are available.

#### Upcoming Endpoints

17. **Upcoming program listings (by callsign, program_id OR tms_id for a series)**
    - `program_id` = PBS internal primary key
    - `tms_id` = Gracenote (program) ID (must be series ID, starting with "SH")
    - Response includes:
      - Gracenote images by default, when available
      - Gracenote genre, extra descriptions, cast, and crew, when available
    *Requires authentication*

18. **Upcoming show listings (by callsign and either episode_id or onetimeonly_id)**
    - Response includes:
      - Gracenote images by default, when available
      - Gracenote genre, extra descriptions, cast, and crew, when available
    *Requires authentication*

19. **Upcoming episode listings (by callsign and Gracenote TMS ID for episodes)**
    - Response includes Gracenote images by default, when available
    *Requires authentication*

20. **Upcoming one time only (OTO) listings (by callsign and Gracenote TMS ID for OTO)**
    - Response includes:
      - Gracenote images by default, when available
      - Gracenote genre, extra descriptions, cast, and crew, when available
    *Requires authentication*

#### Other Endpoints

21. **Feed info for a single station (by station_id)**
    *Requires authentication*

22. **Search programs and episodes by callsign and keyword/s**
    - Returns programs and episodes for a particular station, which have the search term/s in the title or description
    - Matched episodes will be returned (independently) even if the parent program does not have a match
    - Response includes: Program/Episode metadata, including Gracenote genre, extra descriptions, cast, and crew, when available. Does not include schedule info.
    *No authentication required*

23. **Search UPCOMING programs and episodes by callsign and keyword/s**
    - Same as above, but only programs and episodes are returned if they have upcoming listings
    *No authentication required*

24. **Search programs and episodes by keyword/s**
    - Returns programs and episodes which have the search term/s in the title or descriptions
    *No authentication required*

28. **Search by callsign to get all sibling (flagship/primary callsign and secondary transmitter/repeater) callsigns**
    *Requires authentication*

31. **Channel/Feed lookup (by callsign, zipcode)**
    *No authentication required*

32. **Channel/Feed lookup (by callsign only)**
    *No authentication required*

33. **Full program list**
    *No authentication required*

## How to Find Station ID

Look up the station_id using the sample (WETA) link below. Just append the station flagship call sign to the end, and in the response, look for the "id" field.

```
https://station.services.pbs.org/api/public/v1/stations/?call_sign=WETA
```

Use this value for the TVSS `/stations/<station_id>/` endpoint.

## Important Fields

| Fields | Description | Example |
|--------|-------------|---------|
| short_name | The internal, short name for a feed, which is provided and managed by Gracenote. Updates to this name should be submitted to the station's Gracenote contact. Changes typically only occur when a stations switches feeds/lineups. | WETADT3 |
| full_name | The station-provided name for a feed, which is displayed to end users. Changes to this name should be submitted to the PBS Digital support team. | WETA Kids |
| external_id | Unique, numeric Gracenote ID. | 32050 |
| start_time | A localized start time for the show. The localization is based on the feed timezone. The feed timezone is based on the timezone of the call sign (or transmitter) for which you want to access shows. Format: HHMM where "H" represents hours (00-24) and "M" minutes. | 0100 |
| duration | The show duration. Format: HHMM where "H" represents hours and "M" minutes. | 130 (one hour, 30 min.), 330 (three hours, 30 min.) |
| minutes | The show length in minutes. | 60 |
| type | One of the following values: 'episode', 'onetimeonly'. | episode |
| episode_title | Only present when type is 'episode'. | Magic in the Air; Everyone Loves Clifford |
| title | If type is 'onetimeonly', this is the title for the onetimeonly. When type is 'episode', this is the episode's parent program title. | If type is 'onetimeonly': Doctor Zhivago<br>If type is 'episode': Antiques Roadshow |
| program_id | Only present when type is 'episode' and can be used to identify the program related to this episode in other endpoints. | 75 |
| show_id | This will be used to identify the 'episode' and 'onetimeonly' resources and uses the following convention: {type}_{id}. | episode_6692, onetimeonly_1234 |
| closed_captions_available | For this listing, does it have closed captioning? | true, false |
| airing_type | For this listing, is it a re-run or the first time this show is broadcast? | Taped |
| broadcast_hd | For this listing, is it in High Definition (HD)? | true |
| broadcast_stereo | For this listing, is the audio in stereo? | false |
| broadcast_animated | For this listing, is this an animated show? | true, false |
| nola_root | NOLA root (PBS program ID). Short alpha (4 chars) | AUCL |
| nola_episode | NOLA episode (PBS episode ID. Short positive integer (6 chars) | 390401 |
| episode_description | Only present when type is 'episode'. | Jessica and Hector's plan... |
| description | If type is 'episode' this is the program description. | A revival of the classic... |
| timezone | Timezone for requested station. | America/New_York, America/Anchorage |
| image | Array of Gracenote-provided images hosted by PBS (ITS), when available. Value/Array will be empty when there are no images available from Gracenote. | See example JSON structure below |

### Image Field Example
```json
{
  "type": "image/jpeg",
  "image": "https://image.pbs.org/gracenote/pbsd.tmsimg.com/assets/p15694932_b_h9_aa.jpg",
  "width": "1440",
  "height": "1080",
  "caption": "",
  "updated_at": "2018-08-31T20:05:00Z",
  "external_profile": "Banner-L2"
}
```

## Related Pages

- [Changelog](Changelog)

---

*For more information or support, please contact the PBS Digital support team.*
