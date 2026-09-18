export const vClickOutside = {
    mounted(el, binding) {
        el.__clickOutsideHandler__ = (event) => {
            if (!el.contains(event.target)) {
                binding.value(event);
            }
        };
        document.addEventListener('mousedown', el.__clickOutsideHandler__);
    },
    unmounted(el) {
        document.removeEventListener('mousedown', el.__clickOutsideHandler__);
    },
};
