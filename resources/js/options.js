module.exports = {
        appName: process.env.MIX_APP_NAME,
        appUrl: process.env.MIX_APP_URL,

        generalOpenTime: process.env.MIX_RESERVATION_GENERAL_OPEN_TIME,
        generalCloseTime: process.env.MIX_RESERVATION_GENERAL_CLOSE_TIME,
        timeSlotLength: process.env.MIX_RESERVATION_TIMESLOT_LENGTH,
        blockHourQuota: process.env.MIX_RESERVATION_BLOCK_HOUR_QUOTA,
        dateFormat: process.env.MIX_RESERVATION_DATE_FORMAT,
        timeFormat: process.env.MIX_RESERVATION_TIME_FORMAT,
        weeksInAdvance: process.env.MIX_RESERVATION_WEEKS_IN_ADVANCE,

        modalEffect: process.env.MIX_MODAL_EFFECT,


        routes: {
            reservations: {
                'GET': {
                    method: 'GET',
                    uri: 'api/reservations'
                },
                'ADD': {
                    method: 'POST',
                    uri: 'reservation/add'
                },
                'UPDATE': {
                    method: 'POST',
                    uri: 'reservation/update',
                },
                'CONFIRM': {
                    method: 'POST',
                    uri: 'reservation/confirm'
                },
                'DELETE': {
                    method: 'DELETE',
                    uri: 'reservation/delete'
                },
            },
            timeSlots: {
                'GET': {
                    method: 'GET',
                    uri: 'api/timeslots'
                },
            },
            businessHours: {
                'GET': {
                    method: 'GET',
                    uri: 'api/business_hours'
                },
            },
            resources: {
                'GET': {
                    method: 'GET',
                    uri: 'api/rooms'
                }
            }
        }
};
