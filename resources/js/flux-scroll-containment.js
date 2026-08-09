const FLUX_LIST_SELECTOR = 'ui-options, [data-flux-command-items], [data-flux-options]';
const nativeScrollIntoView = Element.prototype.scrollIntoView;

function scrollWithinContainer(element, container) {
    const containerRect = container.getBoundingClientRect();
    const elementRect = element.getBoundingClientRect();

    if (elementRect.top < containerRect.top) {
        container.scrollTop -= containerRect.top - elementRect.top;
    } else if (elementRect.bottom > containerRect.bottom) {
        container.scrollTop += elementRect.bottom - containerRect.bottom;
    }
}

Element.prototype.scrollIntoView = function (arg) {
    const options = typeof arg === 'object' && arg !== null ? arg : {};

    // Flux Activatable uses block: "nearest" when activating options after search/filter.
    if (options.block === 'nearest') {
        const list = this.closest(FLUX_LIST_SELECTOR);

        if (list) {
            scrollWithinContainer(this, list);

            return;
        }
    }

    return nativeScrollIntoView.call(this, arg);
};
