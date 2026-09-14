#ifndef EMBARCADO_LED_FEEDBACK_H
#define EMBARCADO_LED_FEEDBACK_H

enum LedModes {
    LED_MODES_OFF = 0,
    LED_MODES_ON,
    LED_MODES_BLINKING
};


class LedFeedBack {
private:
    bool led_is_active = false;
    int led_mode = LED_MODES_OFF;
    unsigned long blink_interval = 800;
    unsigned long turned_on_time = 0;

public:
    void turnLedOn() {
        digitalWrite(LED_BUILTIN, HIGH);
        turned_on_time = millis();
        led_is_active = true;
    }

    void turnLedOff() {
        digitalWrite(LED_BUILTIN, LOW);
        led_is_active = false;
    }
    
    void begin() {
        pinMode(LED_BUILTIN, OUTPUT);
        digitalWrite(LED_BUILTIN, LOW);
        led_is_active = false;
    }

    void poll() {
        switch (led_mode) {
        case LED_MODES_OFF:
            if (led_is_active) turnLedOff();
            break;
        case LED_MODES_ON:
            if (!led_is_active) turnLedOn();
            break;
        case LED_MODES_BLINKING:
            if (led_is_active && millis() - turned_on_time >= 200) {
                turnLedOff();
            } else if (!led_is_active && millis() - turned_on_time >= blink_interval) {
                turnLedOn();
            }
            break;
        }
    }

    void setBlinkInterval(unsigned long interval) {
        blink_interval = interval;
    }

    void setLedMode(LedModes mode) {
        led_mode = mode;
    }

    int getLedMode() {
        return led_mode;
    }
};

LedFeedBack LedFeedBack;

#endif

