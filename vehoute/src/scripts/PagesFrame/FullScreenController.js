
import { ref } from "vue";

export default class FullScreenController {
    static is_fullscreen_ = ref(false);

    static get is_fullscreen() {
        return FullScreenController.is_fullscreen_.value;
    }

    static async enterFullScreen() {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            }
        } catch (err) {
            console.error(
                `Error attempting to enable full-screen mode: ${err.message} (${err.name})`
            );
        }

        FullScreenController.sync();
    }

    static async exitFullScreen() {
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            }
        } catch (err) {
            console.error(
                `Error attempting to exit full-screen mode: ${err.message} (${err.name})`
            );
        }

        FullScreenController.sync();
    }

    static async toggleFullScreen() {
        if (document.fullscreenElement) {
            await FullScreenController.exitFullScreen();
        } else {
            await FullScreenController.enterFullScreen();
        }
    }

    static sync() {
        FullScreenController.is_fullscreen_.value =
            !!document.fullscreenElement;
    }

    static init() {
        document.addEventListener(
            "fullscreenchange",
            FullScreenController.sync
        );

        FullScreenController.sync();
    }

    static destroy() {
        document.removeEventListener(
            "fullscreenchange",
            FullScreenController.sync
        );
    }
}
