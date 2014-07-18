# ExpressionEngine Plugin: Dailee Entries

###This plugin lets you display entries by date headers. 

For example, if you wanted to do something like:

- 2014
  - July
    - 16
      - entry 3
      - entry 2
      - entry 1
    - 15
      - entry 1
  - May
    - 5
      - entry 2
      - entry 1
- 2013
  - December
    - 12
      - entry 1
      

###The following parameters are currently accepted (with the defaults):

- channel="" 
- limit="50" 
- show_year_header="true" 
- year_header_format="Y" 
- show_month_header="true" 
- month_header_format="F" 
- show_day_header="true" 
- day_header_format="j" 

###To only show a day header, with the entry title, like this:

- July 16, 2014
  - entry 3

You would format your tag like so:

```
{exp:dailee_entries 
  channel="channel_short_name" 
  limit="50" 
  show_year_header="false" 
  show_month_header="false" 
  show_day_header="true" 
  day_header_format="F j, Y" 
}
  <li>{title}</li>
{/exp:dailee_entries}
```

Note that:

  1. each "entry" is dropped into an unordered list, so the content inside the tag should start and end with a li tag. (See below for remaining todos)
  2. the format string should be the PHP date formatting equivalent. 
  3. Standard channel entry tags should all work inside the tag pair, but this is not fully tested (see below)

TODOS:

1. ADD A START AND END DATE PARAMETER
2. ADD A WAY TO MODIFY OUTPUT DISPLAY (NESTED/LINEAR, OR SOMETHING LIKE THAT)
3. ADD THE ABILITY TO CONTROL HTML WRAPPING ELEMENTS AROUND THE HEADER AND ENTRY DISPLAY
4. ADD CONFIGURABLE CLASS NAMES TO THE WRAPPING ELEMENTS ( @litzinger )

####Please Note:
--------------------
This plugin was developed for a specific purpose and will not fit every situation I'm sure there are a number of bugs and inefficient methodology. It was an interesting problem to solve, however, and was something I've looked for a number of times. Feedback and/or forking is welcome and appreciated. 
