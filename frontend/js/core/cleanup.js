let cleanupFunctios = [];

export function registerCleanup(fn) {

    cleanupFunctios.push(fn);

}

export function runCleanup() {

    cleanupFunctios.forEach((cleanup) => cleanup());

    cleanupFunctios = [];
    
}