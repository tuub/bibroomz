/**
 * The app's clock.
 *
 * Every datetime that crosses the API is a wall-clock value in the app
 * timezone with no zone attached to it: `CalendarEntryPresenter` sends
 * "2026-06-10 13:00" for a booking the user made at 13:00 local time, and the
 * calendar columns render with `timeZone: "utc"`, so that value reaches the
 * grid unchanged. The database never stored a zone either - the backend runs
 * in that zone (`config('app.timezone')`) and compares against those values
 * directly.
 *
 * The browser's own clock therefore cannot be compared against those values.
 * `dayjs.utc()` is two hours behind Berlin in summer, which is what drew the
 * now-indicator two hours too high and let slots that had already passed still
 * be selected; plain `dayjs()` is only right as long as the browser happens to
 * sit in the app timezone.
 *
 * `appNow()` is the browser's copy of the backend's clock: the current instant
 * shifted by the app timezone's offset and left in UTC mode, so it lands in
 * the same wall-clock space as everything the API hands out, whatever timezone
 * the browser is in. Parse API datetimes with `dayjs.utc()` and they are
 * directly comparable to it.
 */
import dayjs, { type Dayjs } from "dayjs";
import timezone from "dayjs/plugin/timezone";
import utc from "dayjs/plugin/utc";

dayjs.extend(utc);
dayjs.extend(timezone);

/** Minutes the app timezone is ahead of UTC right now, DST included. */
function appUtcOffset(timeZone: string): number {
    try {
        return dayjs().tz(timeZone).utcOffset();
    } catch {
        // An unknown zone name makes dayjs throw; the app's own fallback for a
        // missing timezone is UTC, so stay on it rather than take the page down.
        return 0;
    }
}

export function appNow(timeZone: string): Dayjs {
    return dayjs.utc().add(appUtcOffset(timeZone), "minute");
}

/**
 * A datetime in the shape the API expects: wall clock, no zone. Values already
 * live in that space here, so this only formats - an ISO string would carry a
 * `Z` the backend would read as a real instant and shift by the app timezone's
 * offset. Empty input passes through, since an absent datetime is not a time.
 */
export function appWallClock(value: dayjs.ConfigType): string {
    if (value === "" || value === null || value === undefined) {
        return "";
    }

    return dayjs.utc(value).format("YYYY-MM-DD HH:mm:ss");
}
