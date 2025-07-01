import React from 'react';
import tw from 'twin.macro';
import AutomaticBackupRow from '@/components/server/backups/automatic/AutomaticBackupRow';

interface Props {
    backups: any[];
}

export default ({ backups }: Props) => {
    return (
        <>
            <div css={tw`rounded shadow-md bg-neutral-700 mt-4 mb-2`}>
                <div css={tw`bg-neutral-900 rounded-t p-3 border-b border-black`}>Automatic Backup(s)</div>
            </div>
            {backups.length < 1 ? (
                <p css={tw`text-center text-sm text-neutral-300`}>
                    It looks like there are no automatic backups currently stored for this server.
                </p>
            ) : (
                backups.map((backup, index) => (
                    <AutomaticBackupRow key={backup.uuid} backup={backup} css={index > 0 ? tw`mt-2` : undefined} />
                ))
            )}
        </>
    );
};
