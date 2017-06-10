/*jshint esversion: 6 */
/*jshint sub:true*/
'use strict';

class ArgumentDelegate
{
    constructor(config)
    {
        this.config    = config;
    }

    /**
     * Loop through process arguments and check trigger configured callbacks.
     */
    handleArguments()
    {
        let context = this;
        //noinspection JSUnresolvedVariable
        process.argv.forEach((val) => {
            Object.keys(context.config.argumentMap).forEach((argument) => {
                let callback = context.config.argumentMap[argument];
                if (val === argument) {
                    callback.apply(context, [context.config]);
                }
            });
        });
    }
}

//noinspection JSUnresolvedVariable
module.exports = ArgumentDelegate;