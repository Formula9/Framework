The `Container` class is a modified copy of `Illuminate/Container/Container`.

The original purpose for this was to integrate features that Formula9 would require, 
but that has been replaced by `Nine\Containers\Forge` and `Nine\Containers\DI`. Currently, `Illuminate\Container\Container` 
is used by included Illuminate components, although that may no longer be necessary.

Forge currently uses this version of the `Container` class and references Silex Application (the internal `Pimple\Container`).

DI (a multiplexing container) references the `Illuminate\Container\Container` and the `Pimple\Container` classes.

This `Container` may be removed in future versions of Formula 9.
 
