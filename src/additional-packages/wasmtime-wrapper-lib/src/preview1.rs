/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
// Ports of `ReadOnlyDir` and `ReadOnlyFile` to Preview 1 API.
// Adapted from https://github.com/fermyon/spin/commit/1102b9b117258689343f44cd39fa33346561ebfb
use std::{any::Any, path::PathBuf};
use wasi_common::{
    dir::{OpenResult, ReaddirCursor, ReaddirEntity},
    file::{Advice, FdFlags, FileType, Filestat, OFlags},
    Error, ErrorExt, SystemTimeSpec, WasiDir, WasiFile,
};

pub struct ReadOnlyDir(pub Box<dyn WasiDir>);

#[async_trait::async_trait]
impl WasiDir for ReadOnlyDir {
    fn as_any(&self) -> &dyn Any {
        self
    }

    async fn open_file(
        &self,
        symlink_follow: bool,
        path: &str,
        oflags: OFlags,
        read: bool,
        write: bool,
        fdflags: FdFlags,
    ) -> Result<OpenResult, Error> {
        if write {
            Err(Error::perm())
        } else {
            let open_result = self
                .0
                .open_file(symlink_follow, path, oflags, read, write, fdflags)
                .await?;
            Ok(match open_result {
                OpenResult::File(f) => OpenResult::File(Box::new(ReadOnlyFile(f))),
                OpenResult::Dir(d) => OpenResult::Dir(Box::new(ReadOnlyDir(d))),
            })
        }
    }

    async fn create_dir(&self, _path: &str) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn readdir(
        &self,
        cursor: ReaddirCursor,
    ) -> Result<Box<dyn Iterator<Item = Result<ReaddirEntity, Error>> + Send>, Error> {
        self.0.readdir(cursor).await
    }

    async fn symlink(&self, _old_path: &str, _new_path: &str) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn remove_dir(&self, _path: &str) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn unlink_file(&self, _path: &str) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn read_link(&self, path: &str) -> Result<PathBuf, Error> {
        self.0.read_link(path).await
    }

    async fn get_filestat(&self) -> Result<Filestat, Error> {
        self.0.get_filestat().await
    }

    async fn get_path_filestat(
        &self,
        path: &str,
        follow_symlinks: bool,
    ) -> Result<Filestat, Error> {
        self.0.get_path_filestat(path, follow_symlinks).await
    }

    async fn rename(
        &self,
        _path: &str,
        _dest_dir: &dyn WasiDir,
        _dest_path: &str,
    ) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn hard_link(
        &self,
        _path: &str,
        _target_dir: &dyn WasiDir,
        _target_path: &str,
    ) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn set_times(
        &self,
        _path: &str,
        _atime: Option<SystemTimeSpec>,
        _mtime: Option<SystemTimeSpec>,
        _follow_symlinks: bool,
    ) -> Result<(), Error> {
        Err(Error::perm())
    }
}

pub struct ReadOnlyFile(pub Box<dyn WasiFile>);

#[async_trait::async_trait]
impl WasiFile for ReadOnlyFile {
    fn as_any(&self) -> &dyn Any {
        self
    }

    async fn get_filetype(&self) -> Result<FileType, Error> {
        self.0.get_filetype().await
    }

    #[cfg(unix)]
    fn pollable(&self) -> Option<rustix::fd::BorrowedFd> {
        self.0.pollable()
    }

    #[cfg(windows)]
    fn pollable(&self) -> Option<io_extras::os::windows::RawHandleOrSocket> {
        self.0.pollable()
    }

    fn isatty(&self) -> bool {
        self.0.isatty()
    }

    async fn datasync(&self) -> Result<(), Error> {
        self.0.datasync().await
    }

    async fn sync(&self) -> Result<(), Error> {
        self.0.sync().await
    }

    async fn get_fdflags(&self) -> Result<FdFlags, Error> {
        self.0.get_fdflags().await
    }

    async fn set_fdflags(&mut self, _flags: FdFlags) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn get_filestat(&self) -> Result<Filestat, Error> {
        self.0.get_filestat().await
    }

    async fn set_filestat_size(&self, _size: u64) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn advise(&self, offset: u64, len: u64, advice: Advice) -> Result<(), Error> {
        self.0.advise(offset, len, advice).await
    }

    async fn set_times(
        &self,
        _atime: Option<SystemTimeSpec>,
        _mtime: Option<SystemTimeSpec>,
    ) -> Result<(), Error> {
        Err(Error::perm())
    }

    async fn read_vectored<'a>(&self, bufs: &mut [std::io::IoSliceMut<'a>]) -> Result<u64, Error> {
        self.0.read_vectored(bufs).await
    }

    async fn read_vectored_at<'a>(
        &self,
        bufs: &mut [std::io::IoSliceMut<'a>],
        offset: u64,
    ) -> Result<u64, Error> {
        self.0.read_vectored_at(bufs, offset).await
    }

    async fn write_vectored_at<'a>(
        &self,
        _bufs: &[std::io::IoSlice<'a>],
        _offset: u64,
    ) -> Result<u64, Error> {
        Err(Error::perm())
    }

    async fn seek(&self, pos: std::io::SeekFrom) -> Result<u64, Error> {
        self.0.seek(pos).await
    }

    async fn peek(&self, buf: &mut [u8]) -> Result<u64, Error> {
        self.0.peek(buf).await
    }

    fn num_ready_bytes(&self) -> Result<u64, Error> {
        self.0.num_ready_bytes()
    }

    async fn readable(&self) -> Result<(), Error> {
        self.0.readable().await
    }

    async fn writable(&self) -> Result<(), Error> {
        Err(Error::perm())
    }
}
